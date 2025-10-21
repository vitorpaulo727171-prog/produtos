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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
            margin: 0;
            padding: 15px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        
        .container { 
            max-width: 100%; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            left: 0;
            top: 0;
            background: #6c757d;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
            transform: translateX(-2px);
        }
        
        .header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 8px;
            line-height: 1.2;
            padding: 0 60px;
        }
        
        .header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .search-container {
            margin: 20px 0;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 14px 50px 14px 45px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .search-actions {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
        }
        
        .clear-btn {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.3s;
        }
        
        .clear-btn:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
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
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .form-section h2 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .form-group { 
            margin: 12px 0; 
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        
        input, textarea { 
            width: 100%; 
            padding: 14px; 
            margin: 4px 0; 
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            -webkit-appearance: none;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn { 
            background: #667eea; 
            color: white; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            width: 100%;
            -webkit-appearance: none;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary { background: #667eea; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        
        .btn-secondary { 
            background: #6c757d; 
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
            min-width: 380px;
        }
        
        th, td { 
            padding: 12px 10px; 
            border-bottom: 1px solid #dee2e6; 
            text-align: left; 
            font-size: 0.85rem;
        }
        
        th { 
            background: #f8f9fa; 
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }
        
        .btn-sm {
            padding: 10px 12px;
            font-size: 0.8rem;
            width: auto;
            flex: 1;
        }
        
        .stock-low {
            color: #dc3545;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .stock-ok {
            color: #28a745;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .search-results-info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-results {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            font-style: italic;
        }
        
        .form-row {
            display: block;
        }
        
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        /* Mobile First Styles */
        
        /* Small phones (320px - 360px) */
        @media (max-width: 360px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 15px;
                border-radius: 8px;
            }
            
            .header h1 {
                font-size: 1.6rem;
                padding: 0 50px;
            }
            
            .back-button {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .form-section {
                padding: 15px;
            }
            
            .form-section h2 {
                font-size: 1.2rem;
            }
            
            input, textarea {
                padding: 12px;
                font-size: 16px;
            }
            
            .btn {
                padding: 12px 16px;
                font-size: 0.95rem;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.8rem;
            }
            
            .search-input {
                padding: 12px 45px 12px 40px;
            }
        }
        
        /* Tablets and larger phones (min-width: 768px) */
        @media (min-width: 768px) {
            body {
                padding: 25px;
            }
            
            .container {
                max-width: 700px;
                padding: 30px;
            }
            
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            
            .button-group {
                flex-direction: row;
            }
            
            .btn {
                width: auto;
            }
            
            .btn-sm {
                flex: none;
            }
        }
        
        /* Desktop (min-width: 1024px) */
        @media (min-width: 1024px) {
            .container {
                max-width: 1000px;
            }
        }
        
        /* Very small devices (max-width: 320px) */
        @media (max-width: 320px) {
            body {
                padding: 8px;
            }
            
            .container {
                padding: 12px;
            }
            
            .header h1 {
                font-size: 1.4rem;
                padding: 0 40px;
            }
            
            .form-section {
                padding: 12px;
            }
            
            .btn {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) {
            .btn:hover {
                transform: none;
                box-shadow: none;
            }
            
            tr:hover {
                background: inherit;
            }
        }
        
        /* iOS specific fixes */
        @supports (-webkit-touch-callout: none) {
            body {
                -webkit-font-smoothing: antialiased;
            }
            
            input, textarea {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="https://msapp.rf.gd" class="back-button">
                ← Voltar
            </a>
            <h1>🛍️ Mercado dos Sabores</h1>
            <p>Gerenciamento de Produtos</p>

        <div class="search-results-info" id="search-info" style="display: none;">
            <span id="search-text">🔍 Resultados da pesquisa</span>
            <span id="search-count">0 produto(s) encontrado(s)</span>
        </div>

        <!-- Formulário de Adicionar/Editar Produto -->
        <div class="form-section">
            <h2>
                <?= $editing_product ? '✏️ Editar Produto' : '➕ Adicionar Produto' ?>
            </h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editing_product ? 'update' : 'add' ?>">
                <?php if ($editing_product): ?>
                    <input type="hidden" name="id" value="<?= $editing_product['id'] ?>">
                <?php endif; ?>
                
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
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="2" 
                              placeholder="Descrição do produto..."><?= $editing_product ? htmlspecialchars($editing_product['descricao'] ?? '') : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="estoque">Estoque Disponível *</label>
                    <input type="number" name="estoque" id="estoque" min="0" 
                           value="<?= $editing_product ? $editing_product['estoque'] : '' ?>" 
                           placeholder="Quantidade em estoque" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn <?= $editing_product ? 'btn-success' : 'btn-primary' ?>">
                        <?= $editing_product ? '💾 Atualizar' : '➕ Adicionar' ?>
                    </button>
                    
                    <?php if ($editing_product): ?>
                        <a href="?" class="btn btn-secondary">❌ Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

            
        <div class="search-container">
                <div class="search-icon">🔍</div>
                <input type="text" 
                       id="search-input"
                       class="search-input" 
                       placeholder="Pesquisar produtos por nome ou descrição..." 
                       autocomplete="off">
                <div class="search-actions">
                    <button type="button" id="clear-search" class="clear-btn" style="display: none;">Limpar</button>
                </div>
            </div>
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

        <!-- Lista de Produtos -->
        <div class="form-section">
            <h2>📋 Produtos Cadastrados (<span id="total-count"><?= count($produtos) ?></span>)</h2>
            
            <div id="produtos-container">
                <?php if (empty($produtos)): ?>
                    <p class="no-results">
                        Nenhum produto cadastrado.
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table id="produtos-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Preço</th>
                                    <th>Estoque</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="produtos-body">
                                <?php foreach ($produtos as $produto): ?>
                                <tr class="produto-item" data-nome="<?= htmlspecialchars(strtolower($produto['nome'])) ?>" data-descricao="<?= htmlspecialchars(strtolower($produto['descricao'] ?? '')) ?>">
                                    <td><strong>#<?= $produto['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($produto['nome']) ?></strong>
                                        <?php if (!empty($produto['descricao'])): ?>
                                            <br><small style="color: #666;"><?= htmlspecialchars($produto['descricao']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></strong></td>
                                    <td>
                                        <span class="<?= $produto['estoque'] > 0 ? 'stock-ok' : 'stock-low' ?>">
                                            <?= $produto['estoque'] ?> un
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?= $produto['id'] ?>" class="btn btn-warning btn-sm">
                                                ✏️
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Excluir <?= htmlspecialchars($produto['nome']) ?>?')">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
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
        // Busca automática em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const clearSearch = document.getElementById('clear-search');
            const searchInfo = document.getElementById('search-info');
            const searchText = document.getElementById('search-text');
            const searchCount = document.getElementById('search-count');
            const totalCount = document.getElementById('total-count');
            const produtosBody = document.getElementById('produtos-body');
            const produtoItems = document.querySelectorAll('.produto-item');
            const tableContainer = document.querySelector('.table-container');
            
            // Mostrar/ocultar botão limpar
            searchInput.addEventListener('input', function() {
                clearSearch.style.display = this.value ? 'block' : 'none';
                filterProducts(this.value.toLowerCase());
            });
            
            // Botão limpar
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.style.display = 'none';
                filterProducts('');
                searchInput.focus();
            });
            
            // Função de filtro
            function filterProducts(searchTerm) {
                let visibleCount = 0;
                
                if (produtoItems.length === 0) return;
                
                produtoItems.forEach(item => {
                    const nome = item.getAttribute('data-nome');
                    const descricao = item.getAttribute('data-descricao');
                    
                    const matches = nome.includes(searchTerm) || descricao.includes(searchTerm);
                    
                    if (matches) {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Atualizar informações da busca
                if (searchTerm) {
                    searchText.textContent = `🔍 Resultados da pesquisa por: "${searchTerm}"`;
                    searchCount.textContent = `${visibleCount} produto(s) encontrado(s)`;
                    searchInfo.style.display = 'flex';
                    totalCount.textContent = visibleCount;
                } else {
                    searchInfo.style.display = 'none';
                    totalCount.textContent = produtoItems.length;
                }
                
                // Mostrar mensagem se não houver resultados
                const noResults = document.querySelector('.no-results');
                if (visibleCount === 0 && searchTerm) {
                    if (!noResults) {
                        const noResultsMsg = document.createElement('p');
                        noResultsMsg.className = 'no-results';
                        noResultsMsg.textContent = `Nenhum produto encontrado para "${searchTerm}".`;
                        produtosBody.innerHTML = '';
                        produtosBody.appendChild(noResultsMsg);
                    }
                }
            }
            
            // Focar no campo de busca quando carregar a página
            setTimeout(() => {
                searchInput.focus();
            }, 100);
        });

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

        // Focar no campo nome quando carregar a página (se não estiver editando)
        document.addEventListener('DOMContentLoaded', function() {
            const nomeField = document.getElementById('nome');
            const searchInput = document.getElementById('search-input');
            
            if (nomeField && !nomeField.value && !searchInput.value) {
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
                    btn.innerHTML = '⏳...';
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

        // Otimização para touch
        document.addEventListener('touchstart', function() {}, { passive: true });
    </script>
</body>
</html>
