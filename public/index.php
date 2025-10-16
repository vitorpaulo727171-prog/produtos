<?php
// Configurações do banco
$db_config = [
    'host' => getenv('MYSQL_HOST') ?: 'trolley.proxy.rlwy.net',
    'port' => getenv('MYSQL_PORT') ?: '52398',
    'user' => getenv('MYSQL_USER') ?: 'root',
    'pass' => getenv('MYSQL_PASSWORD') ?: 'ZefFlJwoGgbGclwcSyOeZuvMGVqmhvtH',
    'name' => getenv('MYSQL_DATABASE') ?: 'railway'
];

// Conexão com banco
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Processar formulário
$message = '';
$error = '';

if ($_POST['action'] === 'add') {
    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $estoque = $_POST['estoque'] ?? '';
    
    if ($nome && $preco && $estoque) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos_pronta_entrega (nome, descricao, preco, estoque) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $_POST['descricao'] ?? '', $preco, $estoque]);
            $message = "Produto adicionado!";
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}

// Buscar produtos
$produtos = $pdo->query("SELECT * FROM produtos_pronta_entrega ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Mercado Sabores</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin: 10px 0; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Mercado dos Sabores - Admin</h1>
        
        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;">
                ✅ <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;">
                ❌ <?= $error ?>
            </div>
        <?php endif; ?>

        <h2>Adicionar Produto</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome do produto" required>
            </div>
            <div class="form-group">
                <textarea name="descricao" placeholder="Descrição"></textarea>
            </div>
            <div class="form-group">
                <input type="number" name="preco" step="0.01" placeholder="Preço" required>
            </div>
            <div class="form-group">
                <input type="number" name="estoque" placeholder="Estoque" required>
            </div>
            <button type="submit">Adicionar Produto</button>
        </form>

        <h2>Produtos Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?= $produto['id'] ?></td>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td><?= htmlspecialchars($produto['descricao'] ?? '') ?></td>
                    <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                    <td><?= $produto['estoque'] ?> unidades</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
