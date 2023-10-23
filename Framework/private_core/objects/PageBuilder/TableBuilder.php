<?php

namespace Application\PageBuilder;

require_once("PageBuilder.php");
require_once("private_core/objects/PageObjects/TableObjects.php");

/**
 * This script defines the class used to build html tables for pages dynamically.
 * This class acts more as an interface between the database and the classes defined in TableObjects.
 * This class also works in conjunction with the view "Table", as hyperlinks for pages and column sorting refer to it.
 * 
 * @property ?string $heading The title of the table to display in the <caption> element.
 * @property array $columns An array of TableColumn objects
 * @property int $maxRows The maximum number of rows for the table to display at a time.
 * @property int $page The page of the table to display. If the number of records is greater than the $maxRows value, the table will be split into pages.
 * @property ?array $rowHyperlink A URL that each row will direct to if clicked. Optional.
 * @property int $tableRecordCount The number of records that exist in the source table. (Not the number of records that are displayed to the user)
 * @property ?string $sort The name of the column to sort by.
 * @property ?array $tableData An array containing any special settings for a table. Primarily used with table iFrames via {@see WarRoom\Controller\Controller_Table}
 */
class TableBuilder extends PageElementBuilder
{
	private ?string $heading;
	private array $columns;
	private int $maxRows;
	private int $page;
	private int $tableRecordCount;
	private ?string $sort;
	private ?array $tableData;

	public function __construct(string $id, string $styleName = null, string $heading = null, int $maxRows = 100, int $page = 1, int $recordCount = 0, string $sort = null, ?array $tableData = null)
	{
		parent::__construct($id, $styleName);
		$this->heading = $heading;
		$this->columns = array();
		$this->maxRows = $maxRows;
		$this->page = $page;
		$this->tableRecordCount = $recordCount;
		$this->sort = $sort;
		$this->tableData = $tableData;
	}

	/**
	 * Builds the container created by the class' functions
	 */
	public function buildContainer(): string
	{
		$tableHTML = '';
		// Check if there are any rows before building
		if (count($this->pageElements) > 0) {
			$style = "";
			$heading = "";
			if (!is_null($this->styleName) && $this->styleName !== "") {
				$style = ' class="' . $this->styleName . '"';
			}
			if (!is_null($this->heading)) {
				$heading = '<caption>' . $this->heading . '</caption>';
			}
			$tableHTML = '<div><table id="' . $this->id . '"' . $style . '>' . $heading;
			$tableHTML .= "<thead><tr>";

			$tableDataName = 'TableDataName=' . (isset($this->tableData['TableDataName']) ? $this->tableData['TableDataName'] : '');
			$query = isset($this->tableData['Query']) ? $this->tableData['Query'] : '';

			$sort = "";
			foreach ($this->columns as $columnName => &$column) {
				$tooltip = "";
				if (!is_null($column->getTooltip())) {
					$tooltip = ' title="' . $column->getTooltip() . '"';
				}
				// set the column to be grouped by
				if (isset($this->tableData['groupRowsBy'])) {
					foreach ($this->tableData['groupRowsBy'][0] as $groupColumns) {
						if ($column->getName() === $groupColumns) {
							$column->setGroupedStatus(true);
							break;
						}
					}
				}
				$cleansedColumn = str_replace(' ', '%20', $column->getName());

				// If the current table is already sorted, make sure that only the column that is being sorted has its sort value inverted.
				if (is_null($this->sort)) {
					$sort = $cleansedColumn . ',1';
				} else {
					// Check if the sort is ascending or descending and invert it.
					$sortVals = explode(',', $this->sort);
					if ($sortVals[0] == $column->getName()) {
						if (isset($sortVals[1])) {
							if ($sortVals[1] > 0) {
								$sortVals[1] = 0;
							} else {
								$sortVals[1] = 1;
							}
						} else {
							$sortVals[1] = 1;
						}
						$sort = $sortVals[0] . ',' . $sortVals[1];
					} else {
						$sort = $cleansedColumn . ',1';
					}
				}

				$tableHTML .= '<th' . $tooltip . ' class="table-head-sort"><a href="?' . $tableDataName . "&sort=$sort&page=$this->page&rows=$this->maxRows&q=$query" . /*$this->getValues .*/ '">' . $column->getName() . '</a></th>';
			}
			$tableHTML .= "</tr></thead><tbody>";
			$i = 0;

			$rowSpan = 0;
			if (isset($this->tableData['groupRowsBy'])) {
				$prevGroupRow = null;
				$prevRowValue = null;
				$primaryGroup = &$this->tableData['groupRowsBy'][0];// groupRowsBy[0];

				// Loop through each row and set its rowspan.
				foreach ($this->pageElements as &$row) {
					// check that the column to group rows by exists. (check for a cell that falls under the requested column)
					$groupCell = $row->getCell($primaryGroup);
					if (!is_null($groupCell)) {
						$row->setRowSpan(count($this->pageElements));
						// make sure the current row's group value does not match the previous one.
						if ($prevRowValue != $row->getCell($primaryGroup)->getContent()) {
							// set the previous cell to have the new rowspan if it is not null.
							if (!is_null($prevGroupRow)) {
								$prevGroupRow->setRowSpan($rowSpan);
								$prevGroupRow->setGroupStart(true);
							}
							$prevGroupRow = &$row;
							$prevRowValue = &$row->getCell($primaryGroup)->getContent();
							$rowSpan = 0;
						}
						$rowSpan++;
					}
				}
				$row->setRowSpan($rowSpan);
				$prevGroupRow->setGroupStart(true);
			}
			foreach ($this->pageElements as &$row) {
				if ($i < $this->maxRows) {
					$tableHTML .= $row->rowToHTML($this->columns, $this->tableData['destination'], $this->tableData['destinationVars']);
					$i++;
				} else {
					break;
				}
			}

			$tableHTML .= $this->buildPagination($tableDataName, $sort, $query);

			$tableHTML .= '</tbody></table>';

			// Add row number options:
			$tableHTML .= '<label for="table_rows">Rows:</label><select id="table_rows" onchange="window.location=this.value">';
			if ($this->maxRows === 10) {
				$tableHTML .= '<option value="?' . $tableDataName . '&rows=10" selected>10</option>';
			} else {
				$tableHTML .= '<option value="?' . $tableDataName . '&rows=10&q=' . $query.'">10</option>';
			}

			for ($i = 25; $i <= 100; $i += 25) {
				if ($i == $this->maxRows) {
					$tableHTML .= '<option value="?' . $tableDataName . '&rows=' . $i . '" selected>' . $i . '</option>';
				} else {
					$tableHTML .= '<option value="?' . $tableDataName . '&rows=' . $i . '&q=' . $query.'">' . $i . '</option>';
				}
			}

			$tableHTML .= "</div>";
		} else {
			$tableHTML .= '<p class="alert-box-warning">No records exist</p>';
		}
		return $tableHTML;
	}

	public function columnCount()
	{
		return count($this->columns);
	}

	public function rowCount()
	{
		return count($this->pageElements);
	}

	public function ListElements()
	{
		return $this->pageElements;
	}
	/**
	 * Adds a new row to the table. Takes an array containing the data to be inserted into each column of the table.
	 * If the size of the array being inserted does not match the array size of the columns, any elements outside the bounds of the columns will be ignored.
	 * @param array $content An array containing the values to be added into the row's columns, in order.
	 */
	public function addRow(array $content, array $attributes = null, ?int $rowSpan = null)
	{
		$newRow = new TableRow();

		foreach ($content as $tooltip => &$value) {
			if (!is_object($value) && !is_array($value)) {
				$newRow->addCell(new RowCell($this->cleanseOutput($value, false), false, $newRow, $tooltip, null, $attributes), $this->cleanseColumnName($tooltip));
			} elseif ($value instanceof \DateTime) {
				$newRow->addCell(new RowCell($this->cleanseOutput($value, false), false, $newRow, $tooltip, null, $attributes), $this->cleanseColumnName($tooltip));
			}
		}
		array_push($this->pageElements, $newRow);
	}

	public function addPrimaryRow(array $content, array $attributes = null, array $FirstSubRow)
	{
		$newRow = new TableRow();

		foreach ($content as $tooltip => &$value) {
			if (!is_object($value) && !is_array($value)) {
				$newRow->addCell(new RowCell($this->cleanseOutput($value, false), false, $newRow, $tooltip, null, $attributes), $this->cleanseColumnName($tooltip));
			}
		}

		foreach ($FirstSubRow as $tooltip => &$value) {
			if (!is_object($value) && !is_array($value)) {
				$newRow->addCell(new RowCell($this->cleanseOutput($value, false), false, $newRow, $tooltip, null, null), $this->cleanseColumnName($tooltip));
			}
		}

		array_push($this->pageElements, $newRow);
	}

	/**
	 * Adds a new column to the table. If no rows exist in the table, the new column is added.
	 * If rows already exist, it will append the new column to the end of all rows and if $updateExistingData is True, it will append each existing row's new column with the specified data.
	 * @param string $columnName The name of the new column to add
	 * @param string $tooltip A tooltip to display on the column's heading. Not required
	 * @param ActionType $action If the rows in this column are to perform an action, specify the type of action here. The action is only functional if the parameter $actionDestination is not null.
	 * @param string $actionDestination The destination the action will direct to. Must be a URL/URI. 
	 * @param string $actionVariables An associative array containing values to be appended to the $actionDestination as a GET request.
	 * 				If the table is being created from a DatabaseObject, the keys can be column names from the resultset with a value of "null".
	 * @param mixed $nullCellValue The value to put in place of cells that will contain null data after the addition of this column.
	 * 				Only affects cells that are null due to being created before this column was added.
	 */
	public function addColumn(string $columnName, string $tooltip = null, ActionType $action = ActionType::None, string $actionDestination = null, array $actionVariables = null, mixed $nullCellValue = null)
	{
		// Remove any special characters that are valid in a URL from the column name.
		// Column names are URLs to themselves with GET requests, so omitting these characters reduces chance for navigational error.
		$cleansedName = str_replace(['.', ' ', '&', '?', '=', '\\', '/', '%', '$', '#', '@', '!', '*'], '', $columnName);
		if (!isset($this->columns[$cleansedName])) {
			$this->columns[$cleansedName] = new TableColumn($columnName, $tooltip, $action, $actionDestination, $actionVariables, $nullCellValue);
		}
	}

	/**
	 * Takes an associative array and creates rows and columns from it. 
	 * @param array &$tableData - The array containing the data to be transformed into a table.
	 * @param ActionType $action - The type of action a column in the table will perform. Optional.
	 * @param string $actionDestination - The destination the specified action type will execute/redirect to.
	 * @param string $actionColumn - The name of the column in the table (key in the given array) that will contain the action. If null, all columns will perform the specified action.
	 * @param array $actionVariables - An associative array containing any variables the actionDestination parameter requires.
	 */
	public function arrayToTable(array &$tableData, ActionType $action = ActionType::None, string $actionDestination = null, array $actionVariables = null, string $actionColumn = null)
	{
		// check if the resultset is keys are associative or numeric
		if (isset($tableData[0])) {
			if (!is_array($tableData[0])) {
				$this->addColumn("Error!");
				$this->addRow(array("Could not create table from the data given. Data was malformed or not parseable."));
			} else {
				if (is_null($actionVariables)) {
					$actionVariables = ["this"];
				}
				foreach ($tableData as $row) {
					if ($this->columnCount() === 0) {
						foreach ($row as $header => $val) {
							if ($header === $actionColumn) {
								$this->addColumn($header, null, $action, $actionDestination, $actionVariables);
							} else {
								$this->addColumn($header);
							}
						}
					}
					$this->addRow($row);
				}
			}
		} else if (count($tableData) > 0) {
			foreach ($tableData as $header => $val) {
				if ($header === $actionColumn) {
					$this->addColumn($header, null, $action, $actionDestination, $actionVariables);
				} else {
					$this->addColumn($header);
				}
			}
			$this->addRow($tableData);
		} else {
			$this->addColumn("No Results!");
		}
	}

	/**
	 * Creates the pagination for the table based on the number of pages required by the table.
	 */
	private function buildPagination(string $tableDataName, string $sort, ?string $query = null): string
	{
		$pagination = '<tr class="table-nav"><td class="table-pagination" colspan="' . $this->columnCount() . '">';
		$pages = ceil($this->tableRecordCount / $this->maxRows);
		if ($pages === 0.0) {
			$pages = 1;
		}

		// If there are no more than 10 pages, display all page numbers in the pagination
		if ($pages <= 10) {
			for ($i = 1; $i <= $pages; $i++) {
				if ($this->page == $i) {
					$pagination .= '<a href="?' . $tableDataName . '&page=' . $i . "&$sort&rows=$this->maxRows" . '&q=' . $query . '"><b><u>' . $i . '</u></b></a>';
				} else {
					$pagination .= '<a href="?' . $tableDataName . '&page=' . $i . "&$sort&rows=$this->maxRows" . '&q=' . $query . '">' . $i . '</a>';
				}
			}
		} else {
			// Add buttons to go to first page
			$rangeMin = ($this->page > 3) ? $this->page - 2 : 1;
			$rangeMax = ($this->page + 5) > $pages ? $pages : $this->page + 2;
			if ($this->page > 3) {
				$pagination .= '<a href="?' . $tableDataName . "&page=1&$sort&rows=$this->maxRows" . '"><b><u>&laquo;1</u></b></a>';
				$rangeMin = $this->page - 2;
			}
			if ($this->page < 3) {
				$rangeMax = 5;
			}

			for ($i = $rangeMin; $i <= $rangeMax; $i++) {
				if ($this->page == $i) {
					$pagination .= '<a href="?' . $tableDataName . '&page=' . $i . "&$sort&rows=$this->maxRows" . '"><b><u>' . $i . '</u></b></a>';
				} else {
					$pagination .= '<a href="?' . $tableDataName . '&page=' . $i . "&$sort&rows=$this->maxRows" . '">' . $i . '</a>';
				}
			}

			if (($this->page + 5) < $pages) {
				$pagination .= '<a href="?' . $tableDataName . "&page=$pages&$sort&rows=$this->maxRows" . '"><b><u>' . $pages . '&raquo;</u></b></a>';
			}
		}

		$pagination .= '</td></tr>';
		return $pagination;
	}
	private function cleanseColumnName(string $columnName)
	{
		return str_replace(['.', ' ', '&', '?', '=', '\\', '/', '%', '$', '#', '@', '!', '*'], '', $columnName);
	}
}
