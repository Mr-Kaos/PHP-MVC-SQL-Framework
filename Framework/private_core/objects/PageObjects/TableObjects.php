<?php

namespace Application\PageBuilder;

// This script defines objects used to create tables in the PageBuilder_Table file (TableBuilder class).
enum ActionType: Int
{
		// No action.
	case None = 0;
		// A href link.
	case URL = 1;
		// a button element.
	case Button = 2;
		// A href link that is invisible and makes the whole cell clickable.
	case JSButton = 3;
		// A button with an onClick Value that calls a JS function.
	case Invisible = 4;
}
// Constants used for hyperlink variables. If this constant is found in a hyperlink string, it is replaced with row-specific data,
const SELECTED_ROW = "INSERT_SELECTED_DATA_HERE";
const HYPERLINK_URL_KEY = 'URL';
const HYPERLINK_COLUMN_KEY = 'COLUMN';

/**
 * This class defines a column within a table
 * It contains methods to alter a table cell's value or appearance if a certain action is given.
 * @property string $name The name of the column
 * @property ?string $tooltip A short description of the column when the user hovers their cursor over it.
 * @property ActionType $actionType The type of action the cells under this column will perform, if one is given.
 * @property ?string $actionDestination The destination of the action to direct to. Can be a URL.
 * 					If variables are required, they should be specified within a pair of curly braces, prefixed by a dollar symbol.
 * 					Any variable in the string must exist in the $actionVariables array as an array key.
 * @property array $actionVariables An associative array of column names that will be used in the action.
 * @property mixed $nullCellValue If this column is missing a cell, a new one will be created with the value provided here.
 */
class TableColumn
{
	private string $name;
	private ?string $tooltip;
	private ActionType $actionType;
	private ?string $actionDestination;
	private mixed $nullCellValue;
	private ?array $actionVariables;
	private bool $groupByThis;

	public function __construct(string $name, string $tooltip = null, ActionType $action = ActionType::None, string $actionDestination = null, array $actionVariables = null, mixed $nullCellValue = null)
	{
		$this->name = $name;
		$this->tooltip = $tooltip;
		$this->actionType = $action;
		$this->actionDestination = $actionDestination;
		$this->nullCellValue = $nullCellValue;
		$this->actionVariables = $actionVariables;
		$this->groupByThis = false;
	}

	public function &getName()
	{
		return $this->name;
	}
	public function &getTooltip()
	{
		return $this->tooltip;
	}
	public function &getAction()
	{
		return $this->actionType;
	}
	public function &getActionDest()
	{
		return $this->actionDestination;
	}
	public function &getNullCellValue()
	{
		return $this->nullCellValue;
	}
	public function &getActionVariables()
	{
		return $this->actionVariables;
	}
	public function isGrouped()
	{
		return $this->groupByThis;
	}
	public function setGroupedStatus(bool $status)
	{
		$this->groupByThis = $status;
	}

	public function setAction(ActionType $action, string $actionDestination)
	{
		$this->actionType = $action;
		$this->actionDestination = $actionDestination;
	}
}


/**
 * This object defines a row within a table.
 */
class TableRow
{
	private array $cells;
	private ?string $id;
	private ?string $styleName;
	private ?int $rowSpan;
	private ?string $groupColumn;
	private bool $groupStart;

	public function __construct(string $id = null, string $styleName = null)
	{
		$this->id = $id;
		$this->styleName = $styleName;
		$this->cells = array();
		$this->rowSpan = null;
		$this->groupStart = false;
	}

	/**
	 * Adds a new cell to the row.
	 */
	public function addCell(RowCell $cell, string $columnName)
	{
		$this->cells[$columnName] = &$cell;
	}

	/**
	 * Retrieves a cell by its column name.
	 */
	public function &getCell(string $columnName): ?RowCell
	{
		$cell = null;
		if (isset($this->cells[$columnName])) {
			$cell = $this->cells[$columnName];
		}
		return $cell;
	}

	public function cellCount(): int
	{
		return count($this->cells);
	}

	public function setRowSpan(int $span)
	{
		$this->rowSpan = $span;
	}

	public function getRowSpan()
	{
		return $this->rowSpan;
	}

	public function setGroupColumn(string $column)
	{
		$this->groupColumn = $column;
	}

	public function setGroupStart(bool $isStart)
	{
		$this->groupStart = $isStart;
	}

	public function isGroupStart()
	{
		return $this->groupStart;
	}


	/**
	 * Converts all cell objects into HTML tags and contains them within the specified row tag.
	 * Takes a reference of an array of TableColumn objects so any column-specific attributes for cells being built can be identified and created accordingly. 
	 * @param array $columns - An array of all columns to be built into this row.
	 * @param array $hyperlinkData - An associative array that should contain two keys: "URL" and "COLUMN". Specifies the hyperlink for the row to direct to if clicked.
	 */
	public function rowToHTML(array &$columns = null, string $hyperlink = null, array $hyperlinkData = null)
	{
		$html = '<tr';
		if (!is_null($this->id)) {
			$html = ' id="' . $this->id . '"';
		}
		if (!is_null($this->styleName)) {
			$html = ' class="' . $this->styleName;
		}

		// If there is a hyperlink specified for the row, add a CSS style that makes the row look clickable.
		if (!is_null($hyperlink)) {

			if (is_null($this->styleName)) {
				$html .= ' class="';
			}
			$html .= ' table-row-clickable"';

			if (!is_null($hyperlinkData)) {
				foreach ($hyperlinkData as $var => $val) {
					if (isset($this->cells[$val])) {
						$hyperlink = str_replace('$' . $var, $this->cells[$val]->getContent(), $hyperlink);
					}
				}
			}

			$html .= ' onclick="parent.' . $hyperlink . '"';
		} else {
			// Make sure the class list quotations is closed.
			$html .= '"';
		}
		$html .= ">";

		// To ensure that the number of cells in a row matches the number of columns, the columns will be looped.
		// If the cell number is less than the column number, a new temporary one will be created.
		$cells = "";
		$i = 0;
		foreach ($columns as $columnName => &$column) {
			$style = null;
			$attributes = null;
			if ($column->isGrouped()) {
				if ($this->isGroupStart()) {
					$attributes = array("rowspan" => $this->rowSpan);
				} else {
					$style = "hidden";
				}
			}

			$cell = null;
			if ($i < $this->cellCount()) {
				$cell = $this->getCell($columnName);
			} else {
				// $cleansedName = str_replace(' ', '_', $columnName);
				$cell = new RowCell($column->getNullCellValue(), false, $this, $columnName, $style, $attributes);
			}

			if (!is_null($cell)) {
				$cell->setAttributes($attributes);
				$cell->setStyle($style);
				if (isset($columns[$cell->getColumn()])) {
					$cellColumn = $columns[$cell->getColumn()];

					// If the column has an action set that requires variables, get them now.
					$actionVars = array();
					if (!is_null($cellColumn->getActionVariables())) {
						foreach ($cellColumn->getActionVariables() as $varName => &$varValue) {
							if (($varCol = $this->getCell($varName))) {
								$actionVars[$varName] = $varCol->getContent();
							} else {
								$actionVars[$varName] = $varValue;
							}
						}
					}

					$cells .= $cell->cellToHTML($cellColumn->getAction(), $cellColumn->getActionDest(), $actionVars);
				} else {
					$cells .= $cell->cellToHTML();
				}
			}
			$i++;
		}

		$html .= $cells . "</tr>";
		return $html;
	}
}

/**
 * This class defines a singular cell of a row within a table.
 * @property mixed $content The content the cell contains.
 * @property TableColumn $column A reference to a TableColumn object.
 * @property bool $isHeader Determines if this cell is a header cell or not.
 * @property ?string $styleName The CSS class name this cell should be styled to.
 * @property ?array $attributes This array stores any other attributes of the cell
 */
class RowCell
{
	private mixed $content;
	private ?string $column;
	private bool $isHeader;
	private ?string $styleName;
	private ?array $attributes;
	// private TableRow $row;

	public function __construct(mixed $content, bool $isHeader, TableRow &$row, string $column = null, string $styleName = null, array $attributes = null)
	{
		$this->content = $content;
		$this->isHeader = $isHeader;
		// $this->row = &$row;
		$this->column = &$column;
		$this->styleName = $styleName;
		$this->attributes = $attributes;
	}

	public function &getContent()
	{
		return $this->content;
	}
	public function &getColumn()
	{
		return $this->column;
	}
	public function &getStyleName()
	{
		return $this->styleName;
	}
	public function setStyle(?string $style)
	{
		$this->styleName = $style;
	}
	public function isHeader()
	{
		return $this->isHeader;
	}
	public function setContent(mixed $newContent)
	{
		$this->content = $newContent;
	}
	public function setColumn(TableColumn $column)
	{
		$this->column = &$column;
	}
	public function setAttributes(?array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Generates HTML for the cell.
	 * 
	 * @param ActionType $action The type of action this cell will perform if one is given.
	 * @param string $actionDestination The destination the action will direct to when it is called. Typically a URL.
	 * @param array $actionVariables An associative array of that contains variables that can alter the $actionDestination variable.
	 */
	public function cellToHTML(ActionType $action = null, string $actionDestination = null, array $actionVariables = null)
	{
		$html = "";
		$cellType = "td";
		$cellStyle = "";
		$cellAction = "";
		if ($this->isHeader) {
			$cellType .= "th";
		}
		if (!is_null($this->styleName)) {
			$cellStyle = 'class="' . $this->styleName . '"';
		}

		$cellContent = $this->content;

		if (!is_null($action) && !is_null($actionDestination)) {
			if (!is_null($actionVariables)) {
				// Loop through the variables. If the variable's value is "*this", replace it with the value from the table it specifies. 
				if ($action === ActionType::URL) {
					$actionDestination .= "?";
					$doTrim = true;

					// If the action variable(s) value is "this", make the value itself.
					foreach ($actionVariables as $varName => $value) {
						$actionDestination .= "$varName=$value&";
					}
					if ($doTrim) {
						$actionDestination = substr($actionDestination, 0, strlen($actionDestination) - 1);
					}
				} else {
					foreach ($actionVariables as $var => $val) {
						$actionDestination = str_replace('$' . $var, $val, $actionDestination);
					}
				}
			} else {
				echo "No action variables set for $this->column";
			}

			switch ($action) {
				case ActionType::URL:
					$cellContent = '<a class="parent-redirect" href="' . $actionDestination . '">' . $cellContent . '</a>';
					break;
				case ActionType::Button:
					$cellContent = '<a href="' . $actionDestination . '"><button type="button">' . $cellContent . '</button></a>';
					break;
				case ActionType::JSButton:
					$cellContent = '<button onclick="' . $actionDestination . '">' . $cellContent . '</button>';
					break;
				case ActionType::Invisible:
					$cellAction = $actionDestination;
					break;
			}
		}
		$title = null;
		if (!is_null($this->column) && $this->column !== "") {
			$title = 'title="' . $this->column . '"';
		}
		$cellAttributes = " ";
		if ($this->attributes != null) {
			foreach ($this->attributes as $att => $val) {
				$cellAttributes .= $att . " = " . "'" . $val . "'";
			}
		}
		$html .= "<$cellType $cellStyle $cellAction" . $title . $cellAttributes . '>' . $cellContent . "</$cellType>";
		return $html;
	}
}
