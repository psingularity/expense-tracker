<?php 
    session_start();
    $pdo = new PDO("mysql:host=localhost;dbname=expense_tracker", "root", "");
    $action = $_POST['action'] ?? '';
    $errors= []; 
    $editingExpense = null;
    $message=$_SESSION["message"] ?? "";
    if (isset($_SESSION["message"])){
        unset($_SESSION['message']);
    }
    
    function validateExpense($amount, $category, $description, $date){   
        $errors= [];     
        if (!is_numeric($amount) || $amount <= 0) {
            $errors[] = "Amount must be greater than 0 and be a number.";
        }
        if (empty($category)){
            $errors[] =  "Category is required.";
        }
        if (empty($description)){
            $errors[] =  "Description is required.";
        }
        if (empty($date)){
            $errors[] =  "Date is required.";
        } else {
            $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

            if ( $parsedDate === false || $parsedDate -> format('Y-m-d') !== $date ) {
                $errors[] = "Date is invalid.";
            }
        }

        return $errors;
    }

    function calculateTotal($expenses){
        $sum = 0;
        foreach ($expenses as $expense){
            $sum += $expense["amount"];
        }
        return $sum;
    }
    
    function validateId($id){
        if(filter_var($id, FILTER_VALIDATE_INT) && $id > 0){
            return true;
        } else {
            return false;
        }
    };
   
    if ($_SERVER["REQUEST_METHOD"] === "POST" ){   

        if($action === "add"){             
            $addAmount = $_POST["amount"] ?? "";
            $addCategory = trim($_POST["category"] ?? "");
            $addDescription = trim($_POST["description"] ?? "");
            $addDate = $_POST["date"] ?? "";
            
            $errors = validateExpense($addAmount, $addCategory, $addDescription, $addDate);            

            if (empty($errors)){
                $sql = "INSERT INTO expenses (amount, category, description, date) VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$addAmount, $addCategory, $addDescription, $addDate]);
                $_SESSION['message'] = "Expense added successfully.";                
                header("Location:index.php");
                exit;
            }
        } elseif ($action === "delete"){ 
            $id = $_POST["id"] ?? "";            
            if(validateId($id)){
                $sql = "DELETE FROM expenses WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $_SESSION['message'] = "Expense deleted successfully."; 
                header("Location:index.php");
                exit;
            } else {
                $errors[] = "Invalid expense ID";
            }
        } elseif ($action === 'edit'){
            $id = $_POST["id"] ?? "";
            if(validateId($id)){
                $sql = "SELECT * FROM expenses WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $expense = $stmt->fetch(PDO::FETCH_ASSOC);
                if($expense !== false){
                    $editingExpense = $expense;
                } else {
                   $errors[] = "Expense not found";
                }
            } else {
                $errors[] = "Invalid expense ID";
               
            }
        } elseif ($action === "update"){
            $id = $_POST["id"] ?? "";
            if(validateId($id)){
                $amount = $_POST["amount"] ?? "";
                $category = trim($_POST["category"] ?? "");
                $description = trim($_POST["description"] ?? "");
                $date = $_POST["date"] ?? "";
                              
                $errors = validateExpense($amount, $category, $description, $date);

                if (empty($errors)){
                    $sql = "UPDATE expenses SET amount = ?, category = ?, description = ?, date = ? WHERE id = ?";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$amount, $category, $description, $date, $id]);
                    $_SESSION['message'] = "Expense updated successfully."; 
                    header("Location:index.php");
                    exit;
                } else {
                     $editingExpense = [
                        "id" => $id,
                        "amount" => $amount,
                        "category" => $category,
                        "description" => $description,
                        "date" => $date
                    ];
                }
            } else {
                $errors[] = "Invalid expense ID";               
            }
        } else {
            $errors[] = "Unknown action";
        } 
    }

    $filterByCategory = $_GET["category"] ?? "";
    if(is_string($filterByCategory)){
        $filterByCategory = trim($filterByCategory);
    } else {
        $filterByCategory = "";
    }
 

    if ($filterByCategory === ""){
        $result = $pdo->query("SELECT * FROM expenses");
    } else {
        $sql = "SELECT * FROM expenses WHERE category = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$filterByCategory]);
        $result = $stmt;
    }

    $expenses = $result->fetchAll(PDO::FETCH_ASSOC);

    $summaryResult = $pdo->query("SELECT category, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS expense_count FROM expenses GROUP BY category ORDER BY total DESC");
    $categorySummary = $summaryResult ->fetchAll(PDO::FETCH_ASSOC);
    print_r(empty($categorySummary));
   
    if(!empty($errors)){
        foreach ($errors as $error){
             echo "<p style='color: red'>".$error."</p>"; 
        }              
    }
 
    if (!empty($message)) {
        echo "<p style='color: green'>{$message}</p>";
    }
    
    $sum = calculateTotal($expenses);
?>
<?php if(!empty($categorySummary)){?>
<table>    
    <tr>
        <th>Category</th>
        <th>Total</th>
        <th>expense count</th>
    </tr>
    <?php foreach($categorySummary as $category){?>
        <tr>
            <td><?= htmlspecialchars((string) $category['category'], ENT_QUOTES, 'UTF-8')?></td>
            <td><?= htmlspecialchars((string) $category['total'], ENT_QUOTES, 'UTF-8')?></td>
            <td><?= htmlspecialchars((string) $category['expense_count'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php } ?>    
</table>
<?php } ?> 

<form method="GET">
    <label>
        Категория: 
        <input type="text" name="category" value="<?= htmlspecialchars($filterByCategory, ENT_QUOTES, 'UTF-8') ?>"> 
    </label>
    <button type="submit">Показать</button>
    <a href="index.php">Сбросить</a>
</form>

<form method="POST">
    <label>
        Amount:
        <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string) ($addAmount ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
        Category:
        <input type="text" name="category" value="<?= htmlspecialchars((string) ($addCategory ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
        Description:
        <input type="text" name="description" value="<?= htmlspecialchars((string) ($addDescription ?? ''), ENT_QUOTES, 'UTF-8')?>">
    </label>
    <label>
        Date:
        <input type="date" name="date" value="<?= htmlspecialchars((string) ($addDate ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <button name="action" value="add">Add expense</button>
</form>

<table>    
    <tr>
        <th>Description</th>
        <th>Amount</th>
        <th>Category</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>
    <?php if (empty($expenses)){?>
        <tr><td colspan='5'>No expenses found!</td></tr>
    <?php } else {?>
    <?php foreach ($expenses as $expense){ ?>
        <tr>
            <td><?= htmlspecialchars((string) $expense['description'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $expense['amount'] ?></td> 
            <td><?= htmlspecialchars((string) $expense['category'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $expense['date'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $expense['id']?>">
                    <button name="action" value="delete">Delete</button>
                </form>

                 <form method="POST">
                    <input type="hidden" name="id" value="<?= $expense['id']?>">
                    <button name="action" value="edit">Edit</button>
                </form>               
            </td>
        </tr>        
        <?php } }?>        
        <tr><td colspan=4>Total: </td><td><?= $sum ?></td></tr>
        <tr><td colspan=4>Expenses: </td><td><?= count($expenses) ?></td></tr>
</table>
 <?php if ($editingExpense !== null){?>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editingExpense['id']?>">
        <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string) ($editingExpense['amount']), ENT_QUOTES, 'UTF-8') ?>">
        <input type="text" name="category" value="<?= htmlspecialchars((string) ($editingExpense['category']), ENT_QUOTES, 'UTF-8') ?>">
        <input type="text" name="description" value="<?= htmlspecialchars((string) ($editingExpense['description']), ENT_QUOTES, 'UTF-8')?>">
        <input type="date" name="date" value="<?= htmlspecialchars((string) ($editingExpense['date']), ENT_QUOTES, 'UTF-8') ?>">
        <button name="action" value="update">Update</button>
    </form>
<?php } ?>