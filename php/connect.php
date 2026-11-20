<?php 
    try{
        $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $dsn = "mysql:host=localhost;dbname=albumFlavors";
    $connect = new PDO($dsn, "root", "", $options);
}catch(PDOException $error){
    die("Ошибка подключения к БД: " . $error->getMessage());
}
?>