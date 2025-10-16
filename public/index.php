<?php
// Configurações do banco de dados
$db_config = [
    'host' => getenv('MYSQL_HOST') ?: 'trolley.proxy.rlwy.net',
    'port' => getenv('MYSQL_PORT') ?: '52398',
    'user' => getenv('MYSQL_USER') ?: 'root',
    'pass' => getenv('MYSQL_PASSWORD') ?: 'ZefFlJwoGgbGclwcSyOeZuvMGVqmhvtH',
    'name' => getenv('MYSQL_DATABASE') ?: 'railway'
];

// Headers de segurança
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Conexão com o banco de dados com tratamento de erro
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("<h1>Erro de Conexão</h1><p>Não foi possível conectar ao banco de dados. Verifique as configurações.</p>");
}

// Processar ações do formulário
$action = $_POST['action'] ?? '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    // Validar e sanitizar entradas
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = trim(htmlspecialchars($_POST['nome'] ?? ''));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? ''));
    $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $estoque = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);
    
    if ($action === 'add' && $nome && $preco !== false && $estoque !== false) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos_pronta_entrega (nome, descricao, preco, estoque) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $preco, $estoque]);
            $message = "✅ Produto adicionado com sucesso!";
        } catch (PDOException $e) {
            $error = "❌ Erro ao adicionar produto: " . $e->getMessage();
        }
    } 
    elseif ($action === 'update' && $id && $nome && $preco !== false && $estoque !== false) {
        try {
            $stmt = $pdo->prepare("UPDATE produtos_pronta_entrega SET nome = ?, descricao = ?, preco = ?, estoque = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, $preco, $estoque, $id]);
            $message = "✅ Produto atualizado com sucesso!";
        } catch (PDOException $e) {
            $error = "❌ Erro ao atualizar produto: " . $e->getMessage();
        }
    } 
    elseif ($action === 'delete' && $id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM produtos_pronta_entrega WHERE id = ?");
            $stmt->execute([$id]);
            $message = "✅ Produto excluído com sucesso!";
        } catch (PDOException $e) {
            $error = "❌ Erro ao excluir produto: " . $e->getMessage();
        }
    } else {
        $error = "❌ Dados inválidos enviados!";
    }
}

// Buscar dados
try {
    // Produtos
    $stmt = $pdo->query("SELECT * FROM produtos_pronta_entrega ORDER BY id DESC");
    $produtos = $stmt->fetchAll();
    
    // Pedidos (opcional)
    $pedidos = [];
    try {
        $stmt_pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 10");
        $pedidos = $stmt_pedidos->fetchAll();
    } catch (PDOException $e) {
        // Tabela pode não existir
    }
    
    // Conversas (opcional)
    $conversas = [];
    try {
        $stmt_conversas = $pdo->query("SELECT * FROM conversations ORDER BY created_at DESC LIMIT 5");
        $conversas = $stmt_conversas->fetchAll();
    } catch (PDOException $e) {
        // Tabela pode não existir
    }
    
} catch (PDOException $e) {
    $error = "❌ Erro ao carregar dados: " . $e->getMessage();
    $produtos = [];
    $pedidos = [];
    $conversas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado dos Sabores - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); overflow: hidden; }
        .header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 40px; text-align: center; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .content { padding: 30px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8f9fa; padding: 25px; border-radius: 10px; border: 1px solid #e9ecef; }
        .card h3 { color: #495057; margin-bottom: 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ff6b6b; color: white; }
        .table-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .actions { display: flex; gap: 5px; }
        @media (max-width: 768px) {
            .header h1 { font-size: 2em; }
            .content { padding: 20px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> Mercado dos Sabores</h1>
            <p>Painel de Administração</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <div class="card">
                    <h3><i class="fas fa-boxes"></i> Estoque</h3>
                    <div style="font-size: 2em; font-weight: bold; color: #667eea;"><?php echo count($produtos); ?></div>
                    <div>Produtos Cadastrados</div>
                </div>
                <div class="card">
                    <h3><i class="fas fa-shopping-cart"></i> Pedidos</h3>
                    <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?php echo count($pedidos); ?></div>
                    <div>Pedidos Recentes</div>
                </div>
                <div class="card">
                    <h3><i class="fas fa-comments"></i> Conversas</h3>
                    <div style="font-size: 2em; font-weight: bold; color: #ffc107;"><?php echo count($conversas); ?></div>
                    <div>Conversas Ativas</div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-edit"></i> Gerenciar Produtos</h3>
                <form method="POST" id="productForm">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Nome do Produto *</label>
                            <input type="text" name="nome" id="nome" required>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$) *</label>
                            <input type="number" name="preco" id="preco" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" id="descricao" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Estoque *</label>
                        <input type="number" name="estoque" id="estoque" min="0" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-plus"></i> Adicionar Produto
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cancelEdit()" id="cancelBtn" style="display: none;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </form>
            </div>

            <div class="card">
                <h3><i class="fas fa-list"></i> Produtos Cadastrados</h3>
                <?php if (empty($produtos)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">
                        Nenhum produto cadastrado.
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Preço</th>
                                    <th>Estoque</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?php echo $produto['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($produto['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($produto['descricao'] ?? '-'); ?></td>
                                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span style="color: <?php echo $produto['estoque'] > 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                            <?php echo $produto['estoque']; ?> un
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="btn btn-primary" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($produto)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Excluir este produto?')">
                                                <i class="fas fa-trash"></i> Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('productId').value = product.id;
            document.getElementById('nome').value = product.nome;
            document.getElementById('descricao').value = product.descricao || '';
            document.getElementById('preco').value = product.preco;
            document.getElementById('estoque').value = product.estoque;
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Atualizar Produto';
            document.getElementById('cancelBtn').style.display = 'inline-block';
        }
        
        function cancelEdit() {
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Adicionar Produto';
            document.getElementById('cancelBtn').style.display = 'none';
        }
        
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
