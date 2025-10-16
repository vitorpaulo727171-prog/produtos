<?php
// Configurações do banco de dados para Render
$db_host = getenv('MYSQL_HOST') ?: 'trolley.proxy.rlwy.net';
$db_port = getenv('MYSQL_PORT') ?: 52398;
$db_user = getenv('MYSQL_USER') ?: 'root';
$db_pass = getenv('MYSQL_PASSWORD') ?: 'ZefFlJwoGgbGclwcSyOeZuvMGVqmhvtH';
$db_name = getenv('MYSQL_DATABASE') ?: 'railway';

// Headers para CORS (se necessário)
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Conexão com o banco de dados
try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            } catch (PDOException $e) {
                $error = "❌ Erro ao adicionar produto: " . $e->getMessage();
            }
        } else {
            $error = "❌ Preencha todos os campos obrigatórios corretamente!";
        }
    } elseif ($action === 'update') {
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
            } catch (PDOException $e) {
                $error = "❌ Erro ao atualizar produto: " . $e->getMessage();
            }
        } else {
            $error = "❌ Preencha todos os campos obrigatórios!";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id && is_numeric($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM produtos_pronta_entrega WHERE id = ?");
                $stmt->execute([intval($id)]);
                $message = "✅ Produto excluído com sucesso!";
            } catch (PDOException $e) {
                $error = "❌ Erro ao excluir produto: " . $e->getMessage();
            }
        }
    }
}

// Buscar produtos
try {
    $stmt = $pdo->query("SELECT * FROM produtos_pronta_entrega ORDER BY id DESC");
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "❌ Erro ao buscar produtos: " . $e->getMessage();
    $produtos = [];
}

// Buscar pedidos (opcional)
try {
    $stmt_pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 10");
    $pedidos = $stmt_pedidos->fetchAll();
} catch (PDOException $e) {
    // Tabela pedidos pode não existir, ignora o erro
    $pedidos = [];
}

// Buscar conversas (opcional)
try {
    $stmt_conversas = $pdo->query("SELECT * FROM conversations ORDER BY created_at DESC LIMIT 5");
    $conversas = $stmt_conversas->fetchAll();
} catch (PDOException $e) {
    $conversas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado dos Sabores - Painel Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.95;
            position: relative;
        }
        
        .content {
            padding: 40px;
        }
        
        .alert {
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1em;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.4em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 1.1em;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 20px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 700;
            color: #495057;
            font-size: 1.1em;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 10px 15px;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .status-confirmado { background: #fff3cd; color: #856404; }
        .status-preparando { background: #d1ecf1; color: #0c5460; }
        .status-pronto { background: #d4edda; color: #155724; }
        .status-entregue { background: #e2e3e5; color: #383d41; }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 20px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> Mercado dos Sabores</h1>
            <p>Painel de Administração - Gerenciamento Completo</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard de Estatísticas -->
            <div class="dashboard-grid">
                <div class="card">
                    <h3><i class="fas fa-boxes"></i> Estoque</h3>
                    <div class="stat-number"><?php echo count($produtos); ?></div>
                    <div class="stat-label">Produtos Cadastrados</div>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-shopping-cart"></i> Pedidos</h3>
                    <div class="stat-number"><?php echo count($pedidos); ?></div>
                    <div class="stat-label">Pedidos Recentes</div>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-comments"></i> Conversas</h3>
                    <div class="stat-number"><?php echo count($conversas); ?></div>
                    <div class="stat-label">Conversas Ativas</div>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-chart-line"></i> Valor Estoque</h3>
                    <div class="stat-number">R$ <?php 
                        $total = array_sum(array_column($produtos, 'preco'));
                        echo number_format($total, 2, ',', '.');
                    ?></div>
                    <div class="stat-label">Valor Total em Estoque</div>
                </div>
            </div>
            
            <!-- Formulário de Gerenciamento de Produtos -->
            <div class="card">
                <h3><i class="fas fa-edit"></i> Gerenciar Produtos</h3>
                <form method="POST" id="productForm">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-tag"></i> Nome do Produto *</label>
                            <input type="text" id="nome" name="nome" required 
                                   placeholder="Ex: Brownie Ferrero">
                        </div>
                        
                        <div class="form-group">
                            <label for="preco"><i class="fas fa-dollar-sign"></i> Preço (R$) *</label>
                            <input type="number" id="preco" name="preco" step="0.01" min="0" required 
                                   placeholder="Ex: 4.50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3" 
                                  placeholder="Descrição detalhada do produto..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="estoque"><i class="fas fa-boxes"></i> Estoque Disponível *</label>
                        <input type="number" id="estoque" name="estoque" min="0" required 
                               placeholder="Quantidade em estoque">
                    </div>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-plus"></i> Adicionar Produto
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cancelEdit()" 
                                id="cancelBtn" style="display: none;">
                            <i class="fas fa-times"></i> Cancelar Edição
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de Produtos -->
            <div class="card">
                <h3><i class="fas fa-list"></i> Produtos Cadastrados</h3>
                
                <?php if (empty($produtos)): ?>
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 3em; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>Nenhum produto cadastrado. Adicione o primeiro produto acima.</p>
                    </div>
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
                                    <td><strong>#<?php echo htmlspecialchars($produto['id']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($produto['descricao'] ?? '-'); ?></td>
                                    <td>
                                        <span style="color: #28a745; font-weight: bold;">
                                            R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo $produto['estoque'] > 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                            <?php echo $produto['estoque']; ?> unidades
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($produto)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
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
            
            <!-- Pedidos Recentes -->
            <?php if (!empty($pedidos)): ?>
            <div class="card">
                <h3><i class="fas fa-receipt"></i> Pedidos Recentes</h3>
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
                                <td><strong><?php echo htmlspecialchars($pedido['pedido_id'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($pedido['sender_name']); ?></td>
                                <td>
                                    <span style="color: #28a745; font-weight: bold;">
                                        R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $pedido['status'] ?? 'confirmado';
                                    $statusClass = 'status-' . $status;
                                    $statusText = ucfirst($status);
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
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
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Atualizar Produto';
            submitBtn.className = 'btn btn-success';
            
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            // Scroll para o formulário
            document.getElementById('productForm').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
        
        function cancelEdit() {
            document.getElementById('productId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('descricao').value = '';
            document.getElementById('preco').value = '';
            document.getElementById('estoque').value = '';
            document.getElementById('formAction').value = 'add';
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar Produto';
            submitBtn.className = 'btn btn-primary';
            
            document.getElementById('cancelBtn').style.display = 'none';
        }
        
        // Limpar mensagens após 5 segundos
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
        
        // Prevenir reenvio do formulário
        let formSubmitted = false;
        document.getElementById('productForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return;
            }
            formSubmitted = true;
            setTimeout(() => { formSubmitted = false; }, 3000);
        });
    </script>
</body>
</html>
