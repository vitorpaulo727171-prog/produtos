<?php
// Configurações do banco de dados - usando as mesmas variáveis de ambiente do Node.js
$db_host = getenv('MYSQLHOST') ?: 'trolley.proxy.rlwy.net';
$db_port = getenv('MYSQLPORT') ?: 52398;
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: 'ZefFlJwoGgbGclwcSyOeZuvMGVqmhvtH';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Ações do formulário
$action = $_POST['action'] ?? '';
$message = '';
$error = '';

// Processar ações
if ($action === 'add') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $estoque = $_POST['estoque'] ?? '';
    
    if ($nome && $preco && $estoque) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos_pronta_entrega (nome, descricao, preco, estoque) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, floatval($preco), intval($estoque)]);
            $message = "Produto adicionado com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao adicionar produto: " . $e->getMessage();
        }
    } else {
        $error = "Preencha todos os campos obrigatórios!";
    }
} elseif ($action === 'update') {
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $estoque = $_POST['estoque'] ?? '';
    
    if ($id && $nome && $preco && $estoque) {
        try {
            $stmt = $pdo->prepare("UPDATE produtos_pronta_entrega SET nome = ?, descricao = ?, preco = ?, estoque = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, floatval($preco), intval($estoque), intval($id)]);
            $message = "Produto atualizado com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao atualizar produto: " . $e->getMessage();
        }
    } else {
        $error = "Preencha todos os campos obrigatórios!";
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM produtos_pronta_entrega WHERE id = ?");
            $stmt->execute([intval($id)]);
            $message = "Produto excluído com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao excluir produto: " . $e->getMessage();
        }
    }
}

// Buscar produtos
try {
    $stmt = $pdo->query("SELECT * FROM produtos_pronta_entrega ORDER BY id DESC");
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erro ao buscar produtos: " . $e->getMessage();
    $produtos = [];
}

// Buscar pedidos (opcional)
try {
    $stmt_pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 10");
    $pedidos = $stmt_pedidos->fetchAll();
} catch (PDOException $e) {
    $pedidos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado dos Sabores - Gerenciamento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
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
        
        .section {
            margin-bottom: 40px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1.5em;
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
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
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ Mercado dos Sabores</h1>
            <p>Painel de Gerenciamento - Produtos de Pronta Entrega</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Estatísticas -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($produtos); ?></div>
                    <div class="stat-label">Produtos Cadastrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($pedidos); ?></div>
                    <div class="stat-label">Pedidos Recentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">R$ <?php 
                        $total = array_sum(array_column($produtos, 'preco'));
                        echo number_format($total, 2, ',', '.');
                    ?></div>
                    <div class="stat-label">Valor Total em Estoque</div>
                </div>
            </div>
            
            <!-- Formulário de Adicionar/Editar Produto -->
            <div class="section">
                <h2 class="section-title">📦 Adicionar/Editar Produto</h2>
                <form method="POST" id="productForm">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome">Nome do Produto *</label>
                            <input type="text" id="nome" name="nome" required 
                                   placeholder="Ex: Brownie Ferrero">
                        </div>
                        
                        <div class="form-group">
                            <label for="preco">Preço (R$) *</label>
                            <input type="number" id="preco" name="preco" step="0.01" min="0" required 
                                   placeholder="Ex: 4.50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3" 
                                  placeholder="Descrição detalhada do produto..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="estoque">Estoque Disponível *</label>
                        <input type="number" id="estoque" name="estoque" min="0" required 
                               placeholder="Quantidade em estoque">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        ➕ Adicionar Produto
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cancelEdit()" 
                            id="cancelBtn" style="display: none;">
                        ❌ Cancelar Edição
                    </button>
                </form>
            </div>
            
            <!-- Lista de Produtos -->
            <div class="section">
                <h2 class="section-title">📋 Produtos Cadastrados</h2>
                
                <?php if (empty($produtos)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px;">
                        Nenhum produto cadastrado. Adicione o primeiro produto acima.
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
                                    <td><?php echo htmlspecialchars($produto['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($produto['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($produto['descricao'] ?? '-'); ?></td>
                                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span style="color: <?php echo $produto['estoque'] > 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                            <?php echo $produto['estoque']; ?> unidades
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($produto)); ?>)">
                                            ✏️ Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                🗑️ Excluir
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
            
            <!-- Pedidos Recentes -->
            <?php if (!empty($pedidos)): ?>
            <div class="section">
                <h2 class="section-title">📦 Pedidos Recentes</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Cliente</th>
                                <th>Valor Total</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pedido['pedido_id'] ?? $pedido['id']); ?></td>
                                <td><?php echo htmlspecialchars($pedido['sender_name']); ?></td>
                                <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                <td>
                                    <span style="color: 
                                        <?php echo match($pedido['status']) {
                                            'confirmado' => '#ffc107',
                                            'preparando' => '#17a2b8', 
                                            'pronto' => '#28a745',
                                            'entregue' => '#6c757d',
                                            default => '#dc3545'
                                        }; ?>; font-weight: bold;">
                                        <?php echo ucfirst($pedido['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
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
            document.getElementById('submitBtn').textContent = '💾 Atualizar Produto';
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            // Scroll para o formulário
            document.getElementById('productForm').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
        
        function cancelEdit() {
            document.getElementById('productId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';
            document.getElementById('preco').value = '';
            document.getElementById('estoque').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = '➕ Adicionar Produto';
            document.getElementById('cancelBtn').style.display = 'none';
        }
        
        // Limpar mensagens após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
