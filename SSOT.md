# Single Source of Truth (SSOT) v7.0.0

This is the Complete and Latest Single Source of Truth (SSOT) for Sentrifugo 3.2 development, refactoring, and AI context injection.

---

## I. Architecture & Environment

1. **Target System**: Sentrifugo 3.2 (Zend Framework 1).
2. **Runtime Environment**: PHP 8.3 (Laragon local / Linux production target). Modern PHP 8.x type safety and features are permitted in new Services, Models, and Controllers, provided method signatures overriding legacy Zend Framework 1 parent classes remain signature-compatible. Legacy ZF1 SPL interface methods must use `#[\ReturnTypeWillChange]`, and dynamic property reliance must be guarded with `#[\AllowDynamicProperties]` where required.
3. **Menu IDs**: Module-specific Menu IDs must be defined as constants (e.g., `ETHNICCODE_MENU_ID`) and passed accurately to all logging functions.
4. **Backward Compatibility (Architectural Defanging)**: Legacy files that globally affect the system (e.g., `AccessControl.php`) must never be abruptly deleted. Strip their heavy logic, return safe "dummy" values (e.g., `return true;`), and label them with `@deprecated`.
5. **Universal Parent Menu Routing**: Parent menus **MUST NOT** use the legacy `/#` URL, as this triggers a blank UI screen. Instead, Parent menus must use the Universal Redirector pattern by setting their URL to `/menuredirector/index/pid/[Menu_ID]`. The `MenuredirectorController` dynamically intercepts the request, queries `main_menu` for the child routes belonging to that `pid`, verifies ACL access via `sapp_Global::_checkprivileges()`, and issues a `$this->_redirect()` to the highest-priority permitted child menu (prioritizing the `isdefault` column). This eliminates the need for redundant controllers for every parent menu.
6. **Safe Core Defanging (`Menubuilder.php`)**: To enable Smart Redirectors, the core layout helper (`Zend_View_Helper_Menubuilder`) must be safely defanged. Inside `menubuilder()`, the logic that recursively strips URLs from parent items must be wrapped in a condition:
   ```php
   if (trim($data['url']) == '/#' || trim($data['url']) == '') {
       $parent_url = "";
   } else {
       $parent_url = rtrim(BASE_URL, "/") . $data['url'];
   }
   ```
   This preserves legacy functionality while unblocking modern routing.

---

## II. Database Schema Standards

1. **Table Naming Convention**: All new or refactored tables, controllers, and models must use a singular naming convention (e.g., `main_user`, `main_role`, `NumberformatController`). Plural names are strictly prohibited to ensure consistency and logical alignment with Object-Oriented class definitions and ACL URL routing.
2. **Primary Key Definition**: All surrogate primary keys **MUST** use the exact definition:
   ```sql
   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
   ```
   * **Exception**: For high-frequency transaction tables (e.g., Audit Logs, Assessment Lines), `id` `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` should be used instead to prevent integer overflow.
3. **Standard Columns**: All tables must contain the following:
   * `isactive`: State tracking.
   * `description`: Optional text (`varchar(255)` or `text`).
   * `createdby`: ID of creator (`int`).
   * `createddate`: Datetime of creation (`datetime`).
   * `modifiedby`: ID of modifier (Nullable `int`).
   * `modifieddate`: Datetime of last modification (Nullable `datetime`).
4. **Logic Mapping Table**:

   | Logic | Database State | Application State | UI Element |
   | :---: | :---: | :---: | :---: |
   | **Active** | `'Y'` (char) / `1` (int) | `1` (int) | Checked / Switch On |
   | **Inactive** | `'N'` (char) / `0` (int) | `0` (int) | Unchecked / Switch Off |
   | **Deleted** | `'5'` (char/int) | `5` (int) | Removed from view |

5. **Standard Descriptor Columns**: Lookup tables must standardize primary descriptor columns to `name` and `code` (e.g., renaming `religion` to `name` and adding `code`) to ensure universal compatibility with generic sorting (`$by = 'name'`) and standard Regex validators.
6. **Character Set**: All table modifications/creations must upgrade character sets to `utf8mb4` with collation `utf8mb4_unicode_ci` for modern compatibility.
7. **Table Naming Convention**: All new or refactored tables must use a singular naming convention (e.g., `main_distribution_list`, `main_user`, `main_role`). Plural names are prohibited to ensure consistency and logical alignment with Object-Oriented class definitions.
8. **Mapping Tables (Join Tables)**: When creating many-to-many relationship tables, the following strict template must be used:
   * **Naming**: `map_[parent_entity]_[child_entity]` (e.g., `map_company_businessdomain`).
   * **Foreign Keys**: The composite keys (e.g., `company_id`, `businessdomain_id`) must strictly match the parent table's primary key type (`int unsigned NOT NULL`).
   * **Primary Key**: By default, the composite keys themselves serve as the primary key: `PRIMARY KEY (parent_id, child_id)`. Do **NOT** use an auto-incrementing surrogate `id` column except only when/if the mapping table acts as a "First-Class Entity" (meaning it is a parent to a third table, requiring foreign keys to point to it); in that case, you **MUST** add a surrogate auto-incrementing `id` column to serve as the Primary Key, while maintaining a `UNIQUE KEY (parent_id, child_id)` to enforce data integrity.
   * **Constraints**: Foreign keys **MUST** include `ON DELETE CASCADE ON UPDATE CASCADE`.
   * **Audit/State Columns**: Must include `description` (`varchar`), `isactive` (`tinyint unsigned DEFAULT '1'`), and the four standard audit datetime/user fields.
9. **Polymorphic Mapping Tables**: When an entity (like a Phone Number, Address, or Note) can belong to multiple different types of parent entities, a polymorphic mapping table **MUST** be used instead of multiple nullable foreign keys.
   * **Schema**: Must include `owner_type` and `owner_id`.
   * **Type Safety**: `owner_type` **MUST** be an `ENUM` containing the exact PascalCase names of the allowed entities (e.g., `ENUM('Company', 'BusinessUnit', 'Employee')`). `VARCHAR` is prohibited to ensure data integrity.
   * **Primary/Unique Keys**: Must include a surrogate `id` (PK) and a `UNIQUE KEY (target_entity_id, owner_type, owner_id)` to prevent duplicate mappings.
10. **Polymorphic Attributes (2-Table Exception)**: If UI/UX requirements dictate that an attribute (like a phone number) must be managed exclusively within the parent's form (e.g., the Employee form) to prevent accidental modification of shared records, the "Junction Table" requirement may be bypassed. Instead, the attribute table itself (e.g., `main_phone_number`) must contain the polymorphic columns (`owner_type`, `owner_id`).
11. **Lexicographical Sort Paths (`order_path`)**: Hierarchical tables (like Menus or Categories) **MUST** include an `order_path` column (`VARCHAR(255)`) to facilitate high-performance sorting.
    * The value of this column is constructed by zero-padding the visual order number (e.g., `menuOrder` padded to 5 digits: `00005`) and concatenating it with the parent’s `order_path` using a period separator (e.g., `00001.00005.00002`).
    * This allows the database to execute a single, native string sort (`ORDER BY order_path ASC`) that instantly reconstructs the entire recursive tree in correct pre-order traversal without self-joins.
12. **The Immutable Ledger Pattern (Transaction Tables)**: When a transaction table (e.g., an assessment answer) relies on values from a dictionary/lookup table (e.g., a rating scale), the transaction table **MUST** copy and stamp the mathematical or critical values (e.g., `normalized_value`, `numeric_value`) into its own columns at the exact moment of saving.
    * **Rationale**: It must **NOT** rely on a `JOIN` to the dictionary table for future calculations. This guarantees that if HR alters the dictionary later, historical employee records remain mathematically immune and pristine.

---

## III. Code Organization & Ordering

All classes must adhere to the following structure:

1. **Attribute Organization**: Visibility (`private`, `protected`, `public`), then alphabetical.
2. **Method Organization**: Grouped by Functionality, sorted alphabetically within groups:
   * **Group 1**: Initialization/Lifecycle (`init`, `preDispatch`, `__construct`).
   * **Group 2**: Data Display/Retrieval (`indexAction`, `viewAction`, `getGrid`, `getGridData`).
   * **Group 3**: Modification/Persistence (`addAction`, `editAction`, `saveAction`, `saveRecord`).
   * **Group 4**: Removal (`deleteAction`, `makeactiveinactiveAction`, `deleteRecord`).
   * **Group 5**: AJAX, Integrity Checks, & Internal Helpers (`checkDuplicate`, `checkInUse`, `_prepareForm`).
   * **Group 6 (If Applicable)**: Polymorphic Junction Sync Methods.

---

## IV. Model Standards (Data Layer)

1. **Querying**: Strictly `Zend_Db_Select`. Raw SQL (`$db->query(...)`) is banned. Use `->setIntegrityCheck(false)` for table joins.
2. **Grid Aliasing**: Primary keys must be aliased as `id` (e.g., `'id' => 'm.menu_id'`).
3. **Grid Action Buttons**: To render the standard "Edit/View/Delete" icons, `getGrid()` must include `'action' => 'Action'` as the first element in `$tableHeaders`.
4. **Grid "Add" Button**: Models **MUST NOT** pass an `'add' => 'add'` flag in the grid array. Sentrifugo’s core UI helper renders the Add button automatically based on the user's ACL.
5. **Data Integrity (Dependency Checks)**: Before executing a soft delete (`isactive = 5`), Models must perform a Dependency Check (`public function checkInUse($id)`). Abort deletion and throw an Exception if active child records exist.
6. **Foreign Key Pair Retrieval (Naming Convention)**: When creating methods that return flat associative arrays (`[id => name]`) filtered by a foreign key (for cascading dropdowns), the method name must strictly follow the pattern `getPairsBy[EntityName](...)`. Do **NOT** append `Id` to the entity name in the method signature.
   * **Correct**: `public function getPairsByCountry(int $countryId)`
   * **Incorrect**: `public function getPairsByCountryId(int $countryId)`
7. **Mandatory Methods (Baseline PHP 7.4 Syntax)**:
   * `public function saveRecord(array $data, $id = null)`
   * `public function deleteRecord($id)`
   * `public function toggleStatus($id, $status)`
   * `public function getRecordById($id)`
   * `public function getAllRecords(...)`
   * `public function getPairs($activeOnly = true)`
   * `public function checkDuplicate($field, $value, $excludeId = null)`
   * `public function getGrid(...)`
   * `public function getGridData(...)`
   * `public function getPairsBy[EntityName]($foreignKeyId, $activeOnly = true)` *(If applicable)*
8. **Encapsulation of Mapping Queries**: Controllers must **NEVER** query mapping tables directly using `$db->select()->from('map_table')`. Any retrieval of mapped IDs (e.g., getting all Domain IDs for a specific Company) **MUST** be encapsulated within a specific method in the corresponding Mapping Model (e.g., `public function getDomainIdsByCompany(int $companyId): array`).
9. **Junction Table Synchronization (`sync` methods)**: Models responsible for a "parent" entity in a Many-to-Many relationship (e.g., `Country_Model`) must contain the logic for synchronizing the junction/mapping table (e.g., `map_country_nationality`). This logic must be encapsulated in a `public function sync[Entity]Mappings($parentId, array $childIds)` method. The sync operation must be atomic:
   * Fetch all currently mapped child IDs for the parent.
   * Calculate the `array_diff` to find which child IDs to add and which to remove.
   * Perform a hard `DELETE` from the mapping table for all child IDs in the "toRemove" array.
   * Iterate and `INSERT` new mappings for all child IDs in the "toAdd" array.
   * Handle any special flags (like `is_default`) by resetting all records for the parent and then applying the flag to the specified new default.
10. **Polymorphic Junction Synchronization**: Models handling polymorphic junction tables must implement a `sync[Entity]Mappings($targetId, array $owners)` method. This method **MUST** include a translation array to safely map UI form keys (e.g., `sbunit`) to the exact Database `ENUM` values (e.g., `BusinessUnit`) before executing the `INSERT` statements.
11. **Polymorphic Grid Resolution**: When preparing `getGridData()` for a polymorphic table, the Model **MUST** use `LEFT JOIN`s for all possible parent tables and utilize `new Zend_Db_Expr("COALESCE(t1.name, t2.name, ...)")` to create a unified, sortable, and searchable column alias (e.g., `owner_name`) for the UI DataGrid.
12. **Contextual Grid Sorting**: When generating DataGrids, Models should implement Contextual Sorting for improved UX. If a user applies a specific search filter (e.g., filtering the Menu grid by a specific Parent Menu), the `getGrid()` method **SHOULD** dynamically intercept the request and override the default `$by` sorting column to a more logical field (e.g., overriding alphabetical `menuName` to visual `menuOrder`). This ensures the data is presented intuitively based on the user's current filter context.
13. **Recursive Materialized & Sort Path Synchronization**: Hierarchical tables using pathing columns (both `nav_ids` and `order_path`) **MUST** synchronize both fields atomically to minimize database round-trips.
    * The Model **MUST** implement a single recursive method (`updateHierarchyPaths($id, $newNavIds, $newOrderPath)`) to write both fields in a single SQL statement per node.
    * If a parent node's location or visual order changes, the method **MUST** recurse downward, append each child's ID to `nav_ids`, pad each child's visual order to `order_path`, and cascade the updates completely down the branch.
14. **Hierarchical Grid Filtering (Materialized Paths)**: When implementing grid search filters for hierarchical entities (like Menus or Organizational Units), Models **MUST** avoid PHP-side recursive traversal during data retrieval, as it degrades pagination performance. Instead, Models **MUST** utilize a "Materialized Path" (e.g., the `order_path`, the `nav_ids` column containing `,id1,id2,`). When a parent filter is applied, the Model must query `WHERE nav_ids LIKE '%,[ParentID],%'` to instantly retrieve the parent and all descendants at infinite depth in a single database query.
15. **Hierarchical Filter UI (Breadcrumb Paths)**: When providing dropdown filter data for self-referential/hierarchical tables (like Menus or Categories), the Model **MUST NOT** return a flat list of names. It **MUST** dynamically determine all parent nodes and return a Breadcrumb Path (e.g., `Parent > Child > Grandchild`) mapping to the numeric id to prevent ambiguity and ensure precise filtering.
16. **True Tree Sorting in Grids**: When a user applies a hierarchical filter (e.g., filtering by a Parent), alphabetical sorting destroys the visual context of the returned tree subset. The Model's `getGrid()` method **MUST** intercept this filter state and override the `$by` parameter to sort by multiple columns: `parent ASC, menuOrder ASC`. This groups items by their immediate parent and orders them correctly within that subset, ensuring the grid visually matches the actual application hierarchy.
17. **High-Performance Hierarchical Flattening**: When resolving multi-level tree hierarchies (such as nested menus or sub-departments) for routing, targeting, or permission auditing, developers **MUST** avoid recursive database queries or memory-intensive PHP loops. Instead, leverage denormalized breadcrumb path strings (such as `main_menu.nav_ids`) using a single `Zend_Db_Select` with a structured string search (e.g., `where('nav_ids LIKE ?', '%,' . (int)$parentId . ',%')`). This flattens the entire multi-level tree into a single, highly indexable dataset in a single database transaction, allowing for rapid, prioritized sorting (e.g., `ORDER BY isdefault DESC, menuOrder ASC`) at the database level.
18. **Absolute Bound Aggregation (No PHP Math for Bounds)**: When a calculation relies on the minimum and maximum bounds of a dataset (e.g., calculating a percentage score across a dynamic range), the Model **MUST** use native SQL aggregation (`new Zend_Db_Expr('MIN(column)')`) directly on the database.
    * **Rationale**: Never fetch rows into PHP to perform `min()` or `max()` on an array. This prevents fatal logic errors if `Zend_Db` fails to map a column correctly or if an empty value is coerced to `0` by PHP.
19. **Transaction Nesting Safety (PHP 8+ PDO)**: Any Model or Service method initiating a database transaction must check `!$db->getConnection()->inTransaction()` before calling `beginTransaction()`, and only call `commit()` or `rollBack()` if it initiated the transaction. This prevents fatal `PDOException` errors during nested service calls and transactional PHPUnit tests.
20. **Raw Column Qualification in Grid Sorting**: When `getGridData()` formats `DATE` or `DATETIME` fields using `DATE_FORMAT(...)` under the original column alias (e.g., `cycle_start_date`), the `ORDER BY` clause **MUST** explicitly qualify the raw database column (e.g., `pr.cycle_start_date`) so MySQL sorts chronologically rather than alphabetically by formatted month names.

---

## V. Controller Standards (Logic Layer)

1. **Properties**: Must define: `$_loginUserId`, `$_loginuserRole`, `$_loginuserGroup`, `$_model`, `$_options`.
2. **Lifecycle (`init` & `preDispatch`)**:
   * `init()` fetches Auth data. **Crucial**: Must explicitly assign `$this->view->loginuserRole` and `$this->view->loginuserGroup` so View-level ACL checks work.
   * `preDispatch()` enforces `sapp_Global::_readSession()` and sets `AjaxContext` for `save`, `delete`, and `makeactiveinactive`.
3. **Grid Defaults**: `indexAction()` must default `$sort` to `ASC` and `$by` to the primary human-readable column (e.g., `name`, `code`), **NOT** `id` or `modifieddate`.
4. **Persistence & Transactions**: Must wrap `saveAction` and complex DB operations in `beginTransaction()`, `commit()`, and `rollBack()`. The catch block must contain `$db->rollBack()` and must log the raw error, returning only a safe, generic message to the UI.
5. **Logging**: `sapp_Global::logManager($menuId, $actionFlag, $userId, $recordId)` is mandatory. 
   * **Flags**: `1` = Add, `2` = Edit, `3` = Delete, `4` = ToggleStatus.
6. **AJAX JSON Responses (Strict Contract)**: Controllers must exclusively use `$this->_helper->json($response);` to output JSON (never `echo json_encode(); exit;`).
   * **Success (with Redirect)**: If using FlashMessenger for alerts on the next page, the JSON response must be `{"result": "saved", "nomessage": "yes"}`. The `"nomessage": "yes"` flag is mandatory to prevent Sentrifugo's `hrms.js` from crashing and halting the redirect.
   * **Validation Error**: The Controller must return the raw Zend Form array directly: `$this->_helper->json($form->getMessages());`. Do not flatten the array or wrap it in a `{"result": "error"}` object, as `hrms.js` expects root keys to match HTML input IDs.
   * **Exception**: `{"result": "exception", "message": "Error details."}`
7. **Form Preparation**: `_prepareForm($id = null, $readonly = false)` must populate dynamic dropdowns and handle the read-only state. It must not manipulate the `onclick` attribute or submit button properties. All submission logic remains strictly in the Form class.
8. **Validation Error Extraction**: When `$form->isValid()` fails, flatten the nested Zend Form error array:
   ```php
   foreach ($form->getMessages() as $field => $errors) {
       $msgarray[$field] = current($errors);
   }
   ```
9. **Message Handling**: Direct manipulation of `$_SESSION` for alerts is prohibited. Always use:
   ```php
   $this->_helper->flashMessenger->addMessage(array('success' => 'Message'));
   ```
10. **Poly-Hierarchical Integrity Validation**: For organizational entities (SBU, Division, Department, etc.), controllers **MUST** enforce an "Only One Parent" rule in the `saveAction()`. The entity may attach to any higher-level entity or a recursive entity of its own type.
    * **Implementation**: The controller must count the provided parent IDs (`company_id`, `sbunit_id`, `division_id`, `parent_id`, etc.). If the count of non-empty parent IDs is not exactly 1 (or 0 if top-level orphans are permitted by business rules, though usually 1 is strictly enforced), the controller must throw an Exception and abort the save:
      ```php
      throw new Exception("This unit can only belong to exactly one parent organizational unit.");
      ```
11. **Mandatory Methods (Standard CRUD/Lookup Controller)**:
    1. `public function init()`
    2. `public function preDispatch()`
    3. `public function indexAction()`
    4. `public function viewAction()`
    5. `public function addAction()`
    6. `public function editAction()`
    7. `public function saveAction()`
    8. `public function deleteAction()`
    9. `public function makeactiveinactiveAction()`
    10. `public function addpopupAction()` *(For lookup tables)*
    11. `private function _prepareForm($id = null, $readonly = false)`
12. **Disabled Element Validation Bypass**: If a form element is disabled during an Edit operation (e.g., locking an employee ID after a case is initiated), the browser will not submit it in the POST payload. The Controller's `saveAction` **MUST** dynamically remove the validation requirement (e.g., `$form->getElement('field')->setRequired(false);`) before calling `$form->isValid($request->getPost())` to prevent false validation failures. When a record is actively referenced downstream (`checkInUse($id) === true`), structural/mathematical fields must be disabled in `_prepareForm()`, stripped of validators via `setRequired(false)->clearValidators()` in `saveAction()`, and excluded from the save payload—allowing only explicitly permitted operational fields (e.g., `isactive`, `payment_lag_days`, `end_date`) to be updated.
13. **Polymorphic Mapping Validation**: When saving data to a polymorphic junction table where only one target is allowed to be chosen, controllers **MUST** enforce an "Only One Target" rule in the `saveAction()`. The user may only select ONE target entity (e.g., either an Employee OR a Company). If multiple target IDs are submitted, the controller must throw an Exception and abort the save.
14. **Universal Parent Menu Routing (The Redirector Pattern)**: Parent menus **MUST NOT** use the legacy `/#` URL, as this triggers a blank UI screen in Sentrifugo. Instead, Parent menus must use the Universal Redirector pattern by setting their URL to `/menuredirector/index/pid/[Menu_ID]`. The `MenuredirectorController` must coordinate this using a private helper method `_getLeafRoute($pid)`. This helper calls the Model to fetch the entire flattened subtree (regardless of depth), iterates over the prioritized leaf nodes, checks ACL permissions via `sapp_Global::_checkprivileges()`, and returns the first permitted absolute URL. This ensures deep, nested defaults are prioritized cleanly and securely.
15. **Explicit Absolute Menu Highlighting**: When a Controller uses advanced routing (such as dual-dashboards, parameterized URLs, or Add/Edit forms), the global layout engine will fail to highlight the sidebar. The Controller **MUST** assume responsibility for its UI state. It **MUST** explicitly pass the absolute database-matching URL to the view utilizing `BASE_URL`:
    ```php
    $this->view->activeMenuUrl = rtrim(BASE_URL, '/') . '/module/action';
    ```
    Relative paths are strictly prohibited as they will cause silent sidebar collapses.
16. **Unified Grid Context Injection**: If a single controller utilizes a private helper method (e.g., `_processGrid($context)`) to render multiple dashboards, that private helper is the single authoritative source of truth for the UI context. It is responsible for mapping the `$context` to the absolute URL and injecting `$this->view->activeMenuUrl` before rendering the index view.
17. **Materialized Path Coordination**: When executing a save action on a hierarchical entity, the Controller's `saveAction()` must never attempt to build or modify the materialized path (`nav_ids`/`order_path`) directly.
    * The Controller must retrieve the parent's record to fetch its `nav_ids`/`order_path` string.
    * It must assemble the initial child path (defaulting to `,current_id` in case of `nav_ids` and padded `menuOrder` for `order_path`, if it is a top-level root node).
    * It must delegate the actual saving and cascading path updates directly to the Model's recursive sync method (`$this->_model->updateNavPath($id, $path)` and `$this->_model->updateOrderPath($id, $path)`) within the main database transaction block.
18. **Strict User vs. Employee ID Resolution**: Controllers **MUST** explicitly differentiate between a User ID (`main_users.id`, which controls login access) and an Employee ID (`main_employee.id`, which defines position in the org chart).
    * **Rule**: Any controller filtering data based on organizational hierarchy (e.g., "Show me assessments where I am the evaluator") **MUST** fetch the Employee ID via the `Default_Model_Users::getEmpFromUser()` method during `init()` and pass the Employee ID to the Model. Passing the User ID into HR hierarchy queries will result in catastrophic data misalignment.

---

## VI. Form & File Standards

1. **Form Structure**: Group elements into logical types (Hidden, Text, Select, Textarea, Checkbox, Submit) and order them logically/alphabetically.
2. **Element Naming Parity**: `Zend_Form_Element` names **MUST** exactly match the database column names. Failure to do so breaks `$form->populate($data)` and causes Fatal Null Errors during dropdown population.
3. **Validation Constraints**: Enforce validators such as `->setRequired(true)` and `->addValidator('NotEmpty', false, array(...))` on all mandatory fields.
4. **Standard Regex Validations**: All primary string descriptors (e.g., `name`, `code`, `title`) **MUST** include the following Regex validator to prevent invisible, purely numeric, or maliciously formatted entries:
   * **Codes**: `/^(?=.*[a-zA-Z])[a-zA-Z0-9_\-]+$/` (Requires at least one letter; allows letters, numbers, underscores, and hyphens; no spaces).
   * **Names/Titles**: `/^(?=.*[a-zA-Z])([^ ][a-zA-Z0-9 \-_()&.,]*)$/` (Requires at least one letter; cannot start with a space; allows standard business punctuation).
5. **Attributes**: Use `$this->setAttrib('id', 'formid');` and `$this->setMethod('post');`.
6. **Hidden IDs**: Use `$idElement = new Zend_Form_Element_Hidden('id');` with `setValue($idVal)` fetched from the Request object.
7. **Decorators**: Strip default Zend decorators via `$this->clearDecorators()` and enforce `$element->setDecorators(array('ViewHelper'))`. File elements use `array('File')`.
8. **Submit Button Constraint**: The submit button configuration must reside strictly within the Form class `init()` method (unless absolutely necessary in the controller) and use this exact format:
   ```php
   $submit = new Zend_Form_Element_Submit('submit');
   $submit->setLabel('Save')
          ->setAttribs(array('id' => 'submitbutton', 'class' => 'actbtn_btn'));
   ```
9. **AJAX Submissions (Strict Quoting)**: Submit buttons must strictly use double quotes for the outer attribute, an empty string `''` for the form ID parameter, and **NO** trailing `return false;`. Stringently follow this exact escaping pattern to prevent Zend Form HTML-entity corruption and ensure the callback fires correctly:
   ```php
   $urlAction  = "'module/save/format/json'";
   $jsFunction = "'redirecttocontroller(\\'module\\');'";
   $submit->setAttrib('onclick', "saveDetails($urlAction, '', '', $jsFunction);");
   ```
10. **File Upload Directory Creation**: When handling file destinations, dynamically check/create the directory using `@mkdir(..., 0777, true)` and verify it is writable (`is_writable()`) before calling `$file->setDestination()`.
11. **File Upload Security**: All `Zend_Form_Element_File` elements must define Extension and Size validators. **Banned extensions**: `.php`, `.sh`, `.exe`, `.phtml`.
12. **Dropdown Population**: Never use the legacy utility method `$util->populate_select()` when populating form elements from a Model that implements `getPairs()`. Because `getPairs()` returns a flat associative array (`[1 => 'Active']`), passing a flat array into `populate_select()` causes PHP to interpret the string values as arrays, resulting in single-character dropdown options (e.g., `'A'`). Instead, strictly use Zend's native `addMultiOption()` and `addMultiOptions()`:
    ```php
    $form->element_name->addMultiOption('', 'Select Option'); // Empty placeholder first
    $form->element_name->addMultiOptions($model->getPairs());
    ```
13. **Many-to-Many Relationships (`Zend_Form_Element_Multiselect`)**: When representing a Many-to-Many relationship in a form (e.g., assigning multiple Nationalities to a Country), the `Zend_Form_Element_Multiselect` must be used.
    * The `init()` method of the Form populates the multiselect with all possible options (the "many" side).
    * The `_prepareForm()` method in the Controller is responsible for fetching the currently mapped IDs from the junction table (via a new Model method like `getMappedIds()`) and using `$form->populate()` or `$element->setValue()` to pre-select the items for the user when editing.

---

## VII. View Standards (UI/UX)

1. **Standard Form Layout**: All form content inside `save.phtml`, `view.phtml`, and `addpopup.phtml` must be strictly wrapped using Sentrifugo's standard CSS classes:
   ```html
   <div class="total-form-controller">
       <div class="division">
           <div class="new-form-ui clearb">...</div>
       </div>
   </div>
   ```
2. **Asset Inclusions**: Do **NOT** use `$this->headLink()->appendStylesheet(...)` or `$this->headScript()`. Instead, rely on standard HTML tags:
   ```html
   <link rel="stylesheet" type="text/css" href="<?php echo MEDIA_PATH; ?>css/jquery-te.css" />
   <script type="text/javascript" src="<?php echo MEDIA_PATH; ?>js/scripts.js"></script>
   ```
3. **CSS Conflict Prevention**: Do not manually include `<link href=".../screen.css" />` in module views, as it breaks Sentrifugo's global checkbox styling. The strict inclusion stack for `.phtml` files is:
   1. `jquery-te.css`
   2. `style.css`
   3. `scripts.js`
   4. `materialize.js`
   5. `jquery.validate.min.js`
   6. `jquery-te.min.js`
4. **Client Validations**: `save.phtml` and `addpopup.phtml` must call `<?php sapp_Global::generateClientValidations($this->form); ?>` at the top.
5. **Grid Views (`index.phtml`)**: Do not manually inject `<div id="module_header">`, titles, or "Add" buttons. The Sentrifugo grid helper renders these automatically. The view must only contain the `#message_container` and the `$this->grid()` call.
6. **Dynamic Form Labels**: Hardcoded `<label>` text is prohibited. Fetch dynamically:
   ```php
   <?php echo $this->escape($this->form->elementName->getLabel()); ?>
   ```
7. **Textarea/Description Helpers**: Textareas must be rendered using:
   ```php
   <?php echo sapp_Helper::sd_form_helper($this->msgarray, $this->form, 'description', '', 'fck_editor_height', '', array()); ?>
   ```
8. **Script Injections**: WYSIWYG/JQTE initialization scripts must be unconditional and straightforward. Do not wrap them in `.length` existence checks:
   ```html
   <script type="text/javascript">
   $(document).ready(function() {
       $('#description, #helpText').jqte();
   });
   </script>
   ```
9. **XSS Prevention**: All user-generated strings rendered in `.phtml` must be wrapped in `$this->escape()`.
10. **Form Submissions (The Balanced Approach)**:
    * **Standard Forms**: For standard CRUD forms without file uploads or complex cross-field validation, continue using Sentrifugo's global `saveDetails($url, 'formid', '', $jsFunction)` helper to maintain simplicity and reduce boilerplate JS.
    * **Complex Forms (Merged Forms, File Uploads, Custom Validation)**: For forms requiring file uploads (using `FormData`) or custom client-side validation logic (e.g., the "Only One Parent" rule), developers must bypass `saveDetails()` and implement an explicit `$.ajax()` POST request. This explicit handler must replicate the error-rendering behavior by iterating over the raw Zend Form JSON error array and injecting the messages into the corresponding `<span class="errors" id="errors-[field_id]">` elements.
11. **Read-Only Views (`view.phtml`)**: Echo the disabled form element directly (`echo $this->form->elementName;`). For rich text (`.jqte()`), you must lock it using:
    ```javascript
    $('.jqte_editor').attr('contenteditable', 'false');
    ```
12. **Event Bubbling**: You must use `e.stopPropagation()` on interactive UI elements (like checkboxes inside accordions or custom grid buttons) to prevent triggering parent DOM events.
13. **AJAX Payload Formatting**: Custom `.phtml` AJAX submits must use `FormData($form[0])` with `processData: false` and `contentType: false` to ensure compatibility with file uploads.
14. **UI Blocking**: Developers must use `$.blockUI()` and `$.unblockUI()` with the Sentrifugo loader GIF during any manual AJAX calls to prevent double-submissions.
15. **Robust AJAX Dropdown Pattern (Cascading Selects)**: When updating `<select>` options via AJAX (e.g., Country -> State -> City), the JS handler must be defensively programmed:
    * **Reset**: Immediately reset child dropdowns to their default placeholder (`<option value="">Select...</option>`).
    * **Block UI**: Apply `$.blockUI()` specifically to the parent container of the dropdown being updated (e.g., `$('#state').parent().block(...)`), not the entire page.
    * **Robust Parsing**: Use `$.each(response, function(key, value))` and check `typeof value === 'object'`. If true, extract ID/Name dynamically (`value.id || key`). If false, use `key` and `value` directly.
    * **Re-Initialization**: In the AJAX complete callback, always attempt to re-initialize UI plugins (e.g., `$('select').material_select()`) to ensure the newly injected DOM elements are styled correctly by the frontend framework.
16. **Dual-Routed Grid Context**: If a single Controller/Grid serves multiple contexts (e.g., "My Tasks" vs "Team Tasks"), the view `index.phtml` **MUST** inject the context into the grid's Action URLs. Use `$(document).ajaxComplete(...)` to intercept the grid rendering, find the anchor tags in the action column, and append `/context/<?php echo $this->context; ?>` to the `href` attributes.
17. **Custom Tabs for Merged Forms**: Do **NOT** use external UI libraries (like jQuery UI Tabs) inside Sentrifugo forms, as they conflict with global stylesheets. Implement tabs using native HTML lists and simple jQuery `.hide()` and `.fadeIn()` logic. The click handler **MUST** include `e.preventDefault();` to stop browser routing and ensure responsiveness.
18. **Grid ACL Context**: To ensure the Sentrifugo Grid Helper properly renders Action buttons (View/Edit/Delete), the Controller's `viewAction` or `indexAction` **MUST** explicitly assign the Menu ID to the view (e.g., `$this->view->menu_id = MODULE_MENU_ID;`) if the core layout helper fails to infer it from the request object.
19. **Grid Action Interception (Event Delegation)**: When altering standard Grid Action routing (e.g., injecting a `/context/` parameter, or routing one module's grid to another module's form), developers **MUST NOT** use `$(document).ajaxComplete` to rewrite DOM attributes, nor override global functions. You **MUST** use strict jQuery Event Delegation:
    ```javascript
    $(document).on('click', '#grid_name a', function(e) { ... });
    ```
    The handler must intercept both `href` links and inline `onclick` string matches, execute `e.preventDefault(); e.stopPropagation();`, and manually route via `window.location.href` or explicit `$.ajax` calls.
20. **Overriding Global JS Functions**: For Sentrifugo grids that use hardcoded inline `onclick` functions (like `deleterecord(id, controller)`), Event Delegation is insufficient. You **MUST** override the function at the window scope locally within the specific view file. Example:
    ```javascript
    var originalChangeStatus = window.changestatus;
    window.changestatus = function(controller, id, flag) {
        if (controller === 'target') {
            /* Custom Logic */
        } else {
            originalChangeStatus(controller, id, flag);
        }
    };
    ```
21. **Forced Sidebar Highlighting (The Absolute Receiver)**: The global header (`hrmsheader.phtml`) must be designed as a "dumb receiver" that listens for the `$this->activeMenuUrl` variable. It must use jQuery to find the exact `li[menu-url='...']` matching that absolute string. To handle standard page loads safely, any fallback logic inside the header relying on `$_SERVER['REQUEST_URI']` **MUST** construct an absolute URL by prepending the domain:
    ```php
    var absoluteUri = '<?php echo rtrim(DOMAIN, "/"); ?><?php echo $_SERVER["REQUEST_URI"]; ?>';
    ```
22. **Dynamic Dropdown Wrappers**: When implementing dynamic or polymorphic dropdowns that toggle visibility based on another input, developers **MUST NOT** attempt to `.hide()` or `.show()` the `<select>` element or the generated `.select2-container` directly. Each `<select>` **MUST** be wrapped in a dedicated container:
    ```html
    <div class="dynamic-wrapper" id="wrapper_Type">
    ```
    All jQuery visibility toggling must be applied exclusively to this wrapper `<div>`.
23. **Merged Form Live Math Engines**: When a view requires live mathematical calculations based on user input (e.g., a dynamic "Consolidated Score"), the View **MUST** embed the backend mathematical factors (e.g., weights, normalized percentages) directly into the HTML as `data-*` attributes on the input elements (e.g., `data-weight="1.5"`).
    * **Rationale**: This prevents the JavaScript from needing to make synchronous, blocking AJAX calls to the database during calculation, ensuring a lightning-fast UI.
24. **Master-Detail Form Layout**: When building a merged form that handles both a primary entity and a child/mapping collection:
    * **ALL** fields belonging to the primary database table (including `isactive`, `description`, and `sequence`) **MUST** be rendered in the main body of the form (the Header section).
    * Tabs **MUST** be strictly reserved for rendering child collections, mapping grids, or secondary relationships. Do not place primary table fields inside a tab.
25. **Continuous Sliders & Hidden Payloads**: When utilizing HTML5 `<input type="range">` sliders to represent database dictionary options, the slider's value **MUST NOT** be used as the database ID in the POST payload, as sliders only output sequential steps (e.g., 0, 1, 2).
    * **Rule**: The View **MUST** implement a JavaScript listener (`on('change input')`) that maps the slider's current step index to the true database ID via a hidden data array, and injects that true ID into an `<input type="hidden">` field for the Controller to process.

---

## VIII. The "On-The-Fly" Popup Pattern

When allowing users to add lookup data via a modal iframe, 4 components must align perfectly:

1. **Parent View**: Must contain a hidden `<div>` with `id="[module]Container"` and an `<iframe>` with `id="[module]Cont"`. Without this, the `displaydeptform()` JS function fails silently.
2. **Child Controller (`addpopupAction`)**: Must explicitly switch the layout to the popup shell to strip the global sidebar/header while retaining the HTML document structure:
   ```php
   Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
   ```
   *(Do not use `$this->_helper->layout->disableLayout();` as it destroys the `<body>` and `<head>` tags).*
3. **Child Form**: Must remove the `saveDetails` `onclick` handler:
   ```php
   $form->getElement('submit')->setAttrib('onclick', null);
   ```
   This allows standard POST submission.
4. **Child View (`addpopup.phtml`)**: On success, must trigger the parent DOM update:
   ```php
   parent.closeiframeAddPopup('<?php echo $this->optionsHtml; ?>', 'module', 'dropdown_id', 'Label');
   ```
5. **Child View (`addpopup.phtml`) - Multiselect Fields**:
   Because `closeiframeAddPopup()` is designed for single-select dropdowns, it destructively overwrites the parent `<select>` element, erasing any previously selected items in a Multiselect box. To prevent data loss, you **MUST** "rescue" the existing selections array before calling the global function, append the new ID, and restore the array immediately afterward, forcing a UI refresh.

   **Mandatory Multiselect Success Script**:
   ```html
   <script type="text/javascript">
       $(document).ready(function() {
           var $parentSelect = parent.$('#dropdown_id');
           var newId = "<?php echo $this->newInsertedId; ?>";
           var rescuedSelections = [];

           // 1. Rescue existing selections
           if ($parentSelect.length) {
               rescuedSelections = $parentSelect.val() || [];
               if (parent.$.inArray(newId, rescuedSelections) === -1) {
                   rescuedSelections.push(newId);
               }
           }

           // 2. Call global helper (Double quotes required)
           parent.closeiframeAddPopup("<?php echo $this->optionsHtml; ?>", "module_name", "dropdown_id", "Label");

           // 3. Restore selections and refresh UI plugins
           if ($parentSelect.length) {
               $parentSelect.val(rescuedSelections);
               if (parent.$.isFunction(parent.$.fn.select2)) {
                   $parentSelect.select2();
               } else if (parent.$.isFunction(parent.$.fn.material_select)) {
                   $parentSelect.material_select();
               }
           }
       });
   </script>
   ```
6. **Popup Options Quoting & UI Refresh**: In `addpopup.phtml`, `$this->optionsHtml` **MUST** be wrapped in double quotes (`"<?php echo $this->optionsHtml; ?>"`) inside `parent.closeiframeAddPopup(...)` because `selectOptionBuilder()` emits single-quoted HTML attributes. Pass `$newId` as the 3rd argument to `selectOptionBuilder()` for auto-selection. Never call `material_select()` on Sentrifugo dropdowns (use conditional `select2()` + `.trigger('change')` only).

---

## IX. PHP 8.3 Compatibility, Automated Testing & Crash Prevention

1. **Request Parameter Casting**: PHP 8+ throws Fatal Errors if null is passed to string functions (like `rtrim`). To be forward-compatible, always cast request parameters:
   ```php
   (string) $request->getParam('searchData', '');
   ```
2. **Catching Fatal Errors**: In Controllers, always catch `\Throwable $e` rather than `Exception $e`. `Throwable` catches both standard Exceptions and PHP 7/8 strict Error objects (like `TypeError`), preventing silent White Screens of Death.
3. **View Safety**: Never assume an array exists in a `.phtml` file. Use `isset()` and `!empty()` before accessing indexes like `$dataArray[0]`.
4. **PHPUnit 9+ Automated Testing Standard**: Unit and service tests live under `tests/` and execute via Composer (`vendor/bin/phpunit`) using `phpunit.xml` and `tests/bootstrap.php`. Test classes must extend `PHPUnit\Framework\TestCase`, match their exact filename, type-hint `setUp(): void` and `tearDown(): void`, and wrap DB mutations in transactions rolled back in `tearDown()`.

---

## X. Security & ACL

1. **Strict Explicit Access (Default Deny)**: The ACL Enforcer (`preDispatch` plugin) must operate on a strict "Default Deny" basis. Hardcoded bypasses for Super Admins (e.g., `if ($role == 1) return true;`) are strictly prohibited. All roles must have explicit mappings.
2. **Atomic File Operations**: Any method writing cache files must use Atomic File Swapping to prevent race conditions:
   ```
   Write to tempnam() -> file_put_contents() -> chmod() -> rename($tempFile, $liveFile)
   ```
3. **XSS Prevention**: All user-generated strings rendered in `.phtml` must be wrapped in `$this->escape()`.

---

## XI. Code Maintenance & Refactoring Standards

1. **Absolute SSOT Adherence**: All code generation/refactoring must strictly adhere to the SSOT. Deviation is only permitted with explicit prompt overrides.
2. **Sentrifugo 3.2 / ZF1 Best Practices**: Code must remain fully compatible with ZF1 paradigms. Modern PHP features that break ZF1 are prohibited.
3. **Comprehensive Documentation**: **ALL** methods, classes, and complex logic blocks must include standard PHPDoc blocks (`/** ... */`) with `@param` and `@return`.
4. **Backward Compatibility (Safe Deprecation)**: Existing methods must never be deleted or renamed if there is a risk of breaking dependencies. Preserve them, label with `@deprecated`, and proxy to the new implementation safely.
5. **Architectural Defanging**: Existing methods, classes, or Bootstrap-registered plugins must never be abruptly deleted if legacy views or global registries (`Zend_Registry`) depend on them. Instead, apply the "Defanging Pattern": Strip out the heavy or obsolete business logic, retain the exact class and method signatures, and return safe "dummy" values (e.g., returning `true` for a legacy access check). Always label the defanged class/method with a `@deprecated` DocBlock explaining which new system superseded it. This neutralizes legacy code, reclaims memory, and guarantees zero UI crashes.
6. **Global Header Defanging**: The global `hrmsheader.phtml` must **NEVER** contain hardcoded module names, `if/else` routing blocks, or hardcoded arrays of controllers to determine sidebar states. It must be treated as a "dumb receiver" that listens for the `$this->activeMenuUrl` variable provided by the active Controller, only falling back to `$_SERVER['REQUEST_URI']` if an override is not provided.

---

## XII. Development & Environment Standards

1. **The Capitalization Law (OS Agnosticism)**: All Zend Framework MVC physical filenames **MUST** perfectly match the PascalCase segments of their class names to prevent fatal crashes on case-sensitive production servers (Linux).
   * For multi-word modules treated as a single entity, use all lowercase for the trailing words.
   * **Example**: If the class is `Default_Model_Fiscalyear`, the file **MUST** be exactly `Fiscalyear.php`. Using `FiscalYear.php` will work on Windows (XAMPP) but will trigger a fatal Autoloader 404/500 Not Found error when deployed to Linux.
   * All PHP file paths, include paths, and test paths **MUST** strictly use Linux forward slashes (`/`) or `DIRECTORY_SEPARATOR`; Windows backslashes (`\`) are strictly prohibited.
2. **Ghost 404s & OPcache**: If a newly created or renamed Controller/Action returns a 404 despite correct ACL mappings and routing, developers must force an OPcache invalidation (e.g., by restarting the local Apache server or touching the file) as aggressive opcode caching can mask file system updates.
3. **Repository & Commit Standards**: Commits to the repository (`main` branch) must strictly follow Conventional Commits enforced via Git Hook using exact TitleCase prefixes:
   * `Feat:`
   * `Fix:`
   * `Refactor:`
   * `Revert:`
   * `Security:`
   * `Perf:`
   * `Chore:`
   * `Docs:`
   * `Style:`
   * `UI:`
   * `Build:`
   * `Test:`
   * `CI:`
   * Optional scopes and concise, clutter-free multiline descriptions.