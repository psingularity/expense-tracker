<?php 
    $pdo = new PDO("mysql:host=localhost;dbname=expense_tracker", "root", "");
    $errors= [];

    if ($_SERVER["REQUEST_METHOD"] === "POST" ){        
        $amount = $_POST["amount"] ?? "";
        $category = trim($_POST["category"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $date = $_POST["date"] ?? "";

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
        }

        if (empty($errors)){
            $sql = "INSERT INTO expenses (amount, category, description, date) VALUES (?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$amount, $category, $description, $date]);

            header("Location:index.php");
            exit;
        } 
    }

    $result = $pdo->query("SELECT * FROM expenses");
    $expenses = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expenses as $expense){      
        echo "Название затрат: ".$expense['description'].'<br>';
        echo "Сумма: ".$expense['amount'].'<br>';
        echo "Категория: ".$expense['category'].'<br>';
        echo "Дата: ".$expense['date'].'<br><br>';
    }
    if(!empty($errors)){
        foreach ($errors as $error){
             echo "<p style='color: red'>".$error."</p>"; 
        }              
    }
    
?>
<form method="POST">
    <label>
        Amount:
        <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string) ($amount ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
        Category:
        <input type="text" name="category" value="<?= htmlspecialchars((string) ($category ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
        Description:
        <input type="text" name="description" value="<?= htmlspecialchars((string) ($description ?? ''), ENT_QUOTES, 'UTF-8')?>">
    </label>
    <label>
        Date:
        <input type="date" name="date" value="<?= $date ?? '' ?>">
    </label>
    <button>Add expense</button>
</form>