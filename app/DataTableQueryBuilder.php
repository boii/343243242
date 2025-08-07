<?php
/**
 * DataTableQueryBuilder Class
 *
 * A helper class to build and execute paginated queries for data tables,
 * handling search, filtering, and pagination logic in a reusable way.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Helpers
 * @package  Sterilabel
 * @author   Gemini (Refactored by Your Name)
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

class DataTableQueryBuilder
{
    private mysqli $conn;
    private string $baseTable;
    private string $primaryKey;
    private string $selectColumns = '*';
    private string $baseJoins = '';
    private string $whereClause = ' WHERE 1=1';
    private string $groupBy = '';
    private string $orderBy = '';
    private array $params = [];
    private string $types = '';

    /**
     * Constructor for the query builder.
     *
     * @param mysqli $connection The database connection object.
     * @param string $baseTable  The main table to query from (e.g., 'users u').
     * @param string $primaryKey The primary key of the main table (e.g., 'u.user_id').
     */
    public function __construct(mysqli $connection, string $baseTable, string $primaryKey)
    {
        $this->conn = $connection;
        $this->baseTable = $baseTable;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Sets the columns to be selected.
     *
     * @param string $columns The columns string for the SELECT statement.
     * @return self
     */
    public function setSelectColumns(string $columns): self
    {
        $this->selectColumns = $columns;
        return $this;
    }

    /**
     * Sets the base JOIN clauses for the query.
     *
     * @param string $joins The JOIN string.
     * @return self
     */
    public function setBaseJoins(string $joins): self
    {
        $this->baseJoins = ' ' . $joins;
        return $this;
    }

    /**
     * Sets the GROUP BY clause for the query.
     *
     * @param string $groupBy The GROUP BY string.
     * @return self
     */
    public function setGroupBy(string $groupBy): self
    {
        $this->groupBy = ' ' . $groupBy;
        return $this;
    }
    
    /**
     * Sets the ORDER BY clause for the query.
     *
     * @param string $orderBy The ORDER BY string.
     * @return self
     */
    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = ' ' . $orderBy;
        return $this;
    }

    /**
     * Adds a simple WHERE condition to the query.
     *
     * @param string $condition The SQL condition (e.g., 'status = ?').
     * @param mixed  $value     The value to bind.
     * @param string $type      The data type for bind_param (e.g., 's', 'i').
     * @return self
     */
    public function addCondition(string $condition, $value, string $type): self
    {
        if ($value !== null && $value !== '') {
            $this->whereClause .= " AND ($condition)";
            $this->params[] = $value;
            $this->types .= $type;
        }
        return $this;
    }

    /**
     * Adds a search condition across multiple columns.
     *
     * @param string $query   The search term.
     * @param array  $columns The columns to search against.
     * @return self
     */
    public function addSearch(string $query, array $columns): self
    {
        if (!empty($query) && !empty($columns)) {
            $searchTerm = "%" . $query . "%";
            $searchConditions = array_map(fn($col) => "$col LIKE ?", $columns);
            $this->whereClause .= " AND (" . implode(' OR ', $searchConditions) . ")";
            foreach ($columns as $_) {
                $this->params[] = $searchTerm;
                $this->types .= "s";
            }
        }
        return $this;
    }

    /**
     * Executes the query and returns the paginated results and pagination info.
     *
     * @param int $currentPage    The current page number.
     * @param int $recordsPerPage The number of records per page.
     * @return array An array containing 'data' and 'pagination' info.
     */
    public function getResult(int $currentPage, int $recordsPerPage): array
    {
        // Count total records
        $countQuery = "SELECT COUNT(DISTINCT {$this->primaryKey}) as total FROM {$this->baseTable}{$this->baseJoins}{$this->whereClause}";
        $totalRecords = 0;
        
        $stmtCount = $this->conn->prepare($countQuery);
        if ($stmtCount) {
            if (!empty($this->types)) {
                $stmtCount->bind_param($this->types, ...$this->params);
            }
            $stmtCount->execute();
            $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtCount->close();
        } else {
             error_log("DataTableQueryBuilder Error (Count): " . $this->conn->error);
             return ['data' => [], 'pagination' => []];
        }


        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $offset = ($currentPage - 1) * $recordsPerPage;
        
        // Fetch data for the current page
        $data = [];
        if ($totalRecords > 0) {
            $dataQuery = "SELECT {$this->selectColumns} FROM {$this->baseTable}{$this->baseJoins}{$this->whereClause}{$this->groupBy}{$this->orderBy} LIMIT ? OFFSET ?";
            
            $dataParams = $this->params;
            $dataTypes = $this->types;
            
            array_push($dataParams, $recordsPerPage, $offset);
            $dataTypes .= "ii";

            $stmtData = $this->conn->prepare($dataQuery);
            if ($stmtData) {
                $stmtData->bind_param($dataTypes, ...$dataParams);
                $stmtData->execute();
                $result = $stmtData->get_result();
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $stmtData->close();
            } else {
                 error_log("DataTableQueryBuilder Error (Data): " . $this->conn->error);
            }
        }

        return [
            'data' => $data,
            'pagination' => [
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage
            ]
        ];
    }
}