<?php
/**
 * ExpiryCalculator Class
 *
 * Mengenkapsulasi logika untuk menentukan tanggal kedaluwarsa item sterilisasi
 * berdasarkan hierarki aturan yang kompleks.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category  Utilities
 * @package   Sterilabel
 * @author    Your Name <you@example.com>
 * @license   MIT License
 * @link      null
 */
declare(strict_types=1);

class ExpiryCalculator
{
    private mysqli $conn;
    private int $globalDefaultDays;
    private array $masterDataCache = [
        'instrument' => [],
        'set' => [],
        'packaging' => []
    ];

    public function __construct(mysqli $db_connection, int $defaultDays)
    {
        $this->conn = $db_connection;
        $this->globalDefaultDays = $defaultDays;
    }

    /**
     * Menghitung dan mengembalikan tanggal kedaluwarsa yang sudah diformat.
     */
    public function getExpiryDate(int $itemId, string $itemType, int $packagingTypeId, ?string $itemSnapshotJson): string
    {
        $daysUntilExpiry = $this->calculateDays($itemId, $itemType, $packagingTypeId, $itemSnapshotJson);
        return (new DateTime())->modify("+" . $daysUntilExpiry . " days")->format('Y-m-d H:i:s');
    }

    /**
     * Logika utama untuk menentukan jumlah hari kedaluwarsa.
     */
    private function calculateDays(int $itemId, string $itemType, int $packagingTypeId, ?string $itemSnapshotJson): int
    {
        // 1. Dapatkan masa kedaluwarsa dari jenis kemasan
        $daysFromPackaging = $this->getPackagingExpiryDays($packagingTypeId);

        // 2. Dapatkan masa kedaluwarsa terpendek dari item itu sendiri (atau komponennya)
        $daysFromItem = $this->getItemShortestExpiryDays($itemId, $itemType, $itemSnapshotJson);

        // Jika keduanya tidak ada, gunakan default global
        if ($daysFromPackaging === null && $daysFromItem === null) {
            return $this->globalDefaultDays;
        }

        // Jika salah satu null, gunakan nilai yang ada (utamakan yang bukan null)
        if ($daysFromPackaging === null) {
            return $daysFromItem ?? $this->globalDefaultDays;
        }
        if ($daysFromItem === null) {
            return $daysFromPackaging ?? $this->globalDefaultDays;
        }

        // 3. Jika keduanya ada, ambil nilai TERPENDEK untuk keamanan maksimum
        return min($daysFromPackaging, $daysFromItem);
    }

    private function getPackagingExpiryDays(int $id): ?int
    {
        if (isset($this->masterDataCache['packaging'][$id])) {
            return $this->masterDataCache['packaging'][$id];
        }

        $stmt = $this->conn->prepare("SELECT expiry_in_days FROM packaging_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $expiry = $result['expiry_in_days'] ? (int)$result['expiry_in_days'] : null;
        $this->masterDataCache['packaging'][$id] = $expiry;
        
        return $expiry;
    }

    private function getItemShortestExpiryDays(int $itemId, string $itemType, ?string $snapshotJson): ?int
    {
        if ($itemType === 'instrument') {
            $itemMaster = $this->getMasterDataItem('instrument', $itemId);
            return isset($itemMaster['expiry_in_days']) ? (int)$itemMaster['expiry_in_days'] : null;
        }

        if ($itemType === 'set') {
            $setMaster = $this->getMasterDataItem('set', $itemId);
            $setExpiryOverride = isset($setMaster['expiry_in_days']) ? (int)$setMaster['expiry_in_days'] : null;

            // Jika set memiliki override, langsung gunakan itu
            if ($setExpiryOverride !== null) {
                return $setExpiryOverride;
            }

            // Jika tidak, cari masa terpendek dari semua instrumen di dalamnya
            $snapshot = json_decode($snapshotJson ?? '[]', true);
            if (empty($snapshot) || !is_array($snapshot)) {
                return null; // Tidak ada komponen untuk dianalisis
            }
            
            $expiryValues = [];
            foreach ($snapshot as $snapItem) {
                $instrumentInSet = $this->getMasterDataItem('instrument', (int)$snapItem['instrument_id']);
                if (isset($instrumentInSet['expiry_in_days'])) {
                    $expiryValues[] = (int)$instrumentInSet['expiry_in_days'];
                }
            }
            
            return !empty($expiryValues) ? min($expiryValues) : null;
        }

        return null;
    }

    private function getMasterDataItem(string $type, int $id): ?array
    {
        if (isset($this->masterDataCache[$type][$id])) {
            return $this->masterDataCache[$type][$id];
        }

        $table = ($type === 'instrument') ? 'instruments' : 'instrument_sets';
        $colId = ($type === 'instrument') ? 'instrument_id' : 'set_id';

        $stmt = $this->conn->prepare("SELECT expiry_in_days FROM {$table} WHERE {$colId} = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->masterDataCache[$type][$id] = $result;
        return $result;
    }
}