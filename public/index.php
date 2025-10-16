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
$editing_product = null;

// Verificar se é uma requisição POST e se existe action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ação: Adicionar produto
    if ($action === 'add') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = $_POST['preco'] ?? '';
        $estoque = $_POST['estoque'] ?? '';
        
        if ($nome && is_numeric($preco) && is_numeric($estoque)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO produtos_pronta_entrega (nome, descricao, preco, estoque) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, floatval($preco), intval($estoque)]);
                $message = "✅ Produto adicionado com sucesso!";
            } catch (Exception $e) {
                $error = "❌ Erro ao adicionar produto: " . $e->getMessage();
            }
        } else {
            $error = "❌ Preencha todos os campos obrigatórios corretamente!";
        }
    }

    // Ação: Atualizar produto
    if ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = $_POST['preco'] ?? '';
        $estoque = $_POST['estoque'] ?? '';
        
        if ($id && $nome && is_numeric($preco) && is_numeric($estoque)) {
            try {
                $stmt = $pdo->prepare("UPDATE produtos_pronta_entrega SET nome = ?, descricao = ?, preco = ?, estoque = ? WHERE id = ?");
                $stmt->execute([$nome, $descricao, floatval($preco), intval($estoque), intval($id)]);
                $message = "✅ Produto atualizado com sucesso!";
            } catch (Exception $e) {
                $error = "❌ Erro ao atualizar produto: " . $e->getMessage();
            }
        } else {
            $error = "❌ Preencha todos os campos obrigatórios!";
        }
    }

    // Ação: Excluir produto
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM produtos_pronta_entrega WHERE id = ?");
                $stmt->execute([intval($id)]);
                $message = "✅ Produto excluído com sucesso!";
            } catch (Exception $e) {
                $error = "❌ Erro ao excluir produto: " . $e->getMessage();
            }
        }
    }
}

// Carregar produto para edição (apenas se não for uma submissão de formulário)
if (!$message && !$error && isset($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM produtos_pronta_entrega WHERE id = ?");
        $stmt->execute([intval($id)]);
        $editing_product = $stmt->fetch();
    } catch (Exception $e) {
        $error = "❌ Erro ao carregar produto: " . $e->getMessage();
    }
}

// Buscar produtos
try {
    $produtos = $pdo->query("SELECT * FROM produtos_pronta_entrega ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    $error = "❌ Erro ao carregar produtos: " . $e->getMessage();
    $produtos = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Mercado Sabores</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .form-section h2 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .form-group { 
            margin: 15px 0; 
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            margin: 5px 0; 
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn { 
            background: #667eea; 
            color: white; 
            padding: 12px 25px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary { background: #667eea; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        
        .btn-secondary { 
            background: #6c757d; 
            color: white;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        th, td { 
            padding: 15px; 
            border-bottom: 1px solid #dee2e6; 
            text-align: left; 
        }
        
        th { 
            background: #f8f9fa; 
            font-weight: 600;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }
        
        .stock-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .container {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Mercado dos Sabores - Admin</h1>
            <p>Gerenciamento de Produtos de Pronta Entrega</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert success">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Adicionar/Editar Produto -->
        <div class="form-section">
            <h2>
                <?= $editing_product ? '✏️ Editar Produto' : '➕ Adicionar Novo Produto' ?>
            </h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editing_product ? 'update' : 'add' ?>">
                <?php if ($editing_product): ?>
                    <input type="hidden" name="id" value="<?= $editing_product['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome do Produto *</label>
                        <input type="text" name="nome" id="nome" 
                               value="<?= $editing_product ? htmlspecialchars($editing_product['nome']) : '' ?>" 
                               placeholder="Ex: Brownie Ferrero" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="preco">Preço (R$) *</label>
                        <input type="number" name="preco" id="preco" step="0.01" min="0" 
                               value="<?= $editing_product ? $editing_product['preco'] : '' ?>" 
                               placeholder="Ex: 4.50" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="3" 
                              placeholder="Descrição detalhada do produto..."><?= $editing_product ? htmlspecialchars($editing_product['descricao'] ?? '') : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="estoque">Estoque Disponível *</label>
                    <input type="number" name="estoque" id="estoque" min="0" 
                           value="<?= $editing_product ? $editing_product['estoque'] : '' ?>" 
                           placeholder="Quantidade em estoque" required>
                </div>
                
                <div>
                    <button type="submit" class="btn <?= $editing_product ? 'btn-success' : 'btn-primary' ?>">
                        <?= $editing_product ? '💾 Atualizar Produto' : '➕ Adicionar Produto' ?>
                    </button>
                    
                    <?php if ($editing_product): ?>
                        <a href="?" class="btn btn-secondary">❌ Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Lista de Produtos -->
        <div class="form-section">
            <h2>📋 Produtos Cadastrados (<?= count($produtos) ?>)</h2>
            
            <?php if (empty($produtos)): ?>
                <p style="text-align: center; color: #6c757d; padding: 40px;">
                    Nenhum produto cadastrado. Adicione o primeiro produto acima.
                </p>
            <?php else: ?>
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
                            <td><strong>#<?= $produto['id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($produto['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($produto['descricao'] ?? '-') ?></td>
                            <td><strong>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></strong></td>
                            <td>
                                <span class="<?= $produto['estoque'] > 0 ? 'stock-ok' : 'stock-low' ?>">
                                    <?= $produto['estoque'] ?> unidades
                                </span>
                            </td>
                            <td class="actions">
                                <!-- Botão Editar -->
                                <a href="?edit=<?= $produto['id'] ?>" class="btn btn-warning btn-sm">
                                    ✏️ Editar
                                </a>
                                
                                <!-- Formulário Excluir -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Tem certeza que deseja excluir o produto \"<?= htmlspecialchars($produto['nome']) ?>\"?')">
                                        🗑️ Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-remove mensagens após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            });
        }, 5000);

        // Focar no campo nome quando carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const nomeField = document.getElementById('nome');
            if (nomeField && !nomeField.value) {
                nomeField.focus();
            }
        });

        // Prevenir envio duplo do formulário
        let formSubmitting = false;
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (formSubmitting) {
                    e.preventDefault();
                    return;
                }
                formSubmitting = true;
                
                // Desabilitar botões para evitar clique duplo
                const buttons = this.querySelectorAll('button[type="submit"]');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.setAttribute('data-original-text', originalText);
                    btn.innerHTML = '⏳ Processando...';
                });
                
                setTimeout(() => {
                    formSubmitting = false;
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        const originalText = btn.getAttribute('data-original-text');
                        if (originalText) {
                            btn.innerHTML = originalText;
                        }
                    });
                }, 3000);
            });
        });
    </script>
</body>
</html>
