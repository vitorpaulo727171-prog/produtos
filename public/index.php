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
    
<?php
// ... (código anterior permanece igual)

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
        /* ... (estilos anteriores permanecem iguais) */
        
        .search-loading {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 0.9rem;
            display: none;
        }
        
        .search-results-info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            display: none;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-results {
            text-align: center;
            color: #6c757d;
            padding: 30px;
            font-style: italic;
            display: none;
        }
        
        /* ... (outros estilos permanecem iguais) */
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

         <!-- Barra de Pesquisa -->
         <div class="search-container">
                <div class="search-icon">🔍</div>
                <input type="text" 
                       id="search-input"
                       class="search-input" 
                       placeholder="Pesquisar produtos por nome ou descrição..." 
                       autocomplete="off">
                <div class="search-loading" id="search-loading">⏳</div>
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

        <div class="search-results-info" id="search-info">
            <span id="search-text">🔍 Resultados da pesquisa</span>
            <span id="search-count">0 produto(s) encontrado(s)</span>
        </div>

            
        <!-- Lista de Produtos -->
        <div class="form-section">
            <h2>📋 Produtos Cadastrados (<span id="total-count"><?= count($produtos) ?></span>)</h2>
            
            <div id="produtos-container">
                <?php if (empty($produtos)): ?>
                    <p class="no-results" id="no-products-message">
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
                                <tr class="produto-item" data-search="<?= htmlspecialchars(strtolower($produto['nome'] . ' ' . ($produto['descricao'] ?? ''))) ?>">
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
                    <p class="no-results" id="no-results-message" style="display: none;">
                        Nenhum produto encontrado para sua pesquisa.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Busca automática em tempo real - CORRIGIDA
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const clearSearch = document.getElementById('clear-search');
            const searchInfo = document.getElementById('search-info');
            const searchText = document.getElementById('search-text');
            const searchCount = document.getElementById('search-count');
            const totalCount = document.getElementById('total-count');
            const produtosBody = document.getElementById('produtos-body');
            const noResultsMessage = document.getElementById('no-results-message');
            const noProductsMessage = document.getElementById('no-products-message');
            const tableContainer = document.querySelector('.table-container');
            const searchLoading = document.getElementById('search-loading');
            
            let produtoItems = [];
            let searchTimeout = null;
            
            // Inicializar lista de produtos
            if (produtosBody) {
                produtoItems = Array.from(produtosBody.getElementsByClassName('produto-item'));
            }
            
            // Mostrar/ocultar botão limpar
            searchInput.addEventListener('input', function() {
                clearSearch.style.display = this.value ? 'block' : 'none';
                
                // Mostrar loading durante a digitação
                searchLoading.style.display = 'block';
                
                // Limpar timeout anterior
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Aguardar um pouco antes de filtrar (debounce)
                searchTimeout = setTimeout(() => {
                    filterProducts(this.value.toLowerCase().trim());
                    searchLoading.style.display = 'none';
                }, 300);
            });
            
            // Botão limpar
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.style.display = 'none';
                filterProducts('');
                searchInput.focus();
            });
            
            // Função de filtro corrigida
            function filterProducts(searchTerm) {
                let visibleCount = 0;
                
                // Se não há produtos, não fazer nada
                if (produtoItems.length === 0) return;
                
                // Se a pesquisa está vazia, mostrar todos os produtos
                if (!searchTerm) {
                    produtoItems.forEach(item => {
                        item.style.display = '';
                    });
                    
                    // Ocultar mensagens de pesquisa
                    searchInfo.style.display = 'none';
                    if (noResultsMessage) noResultsMessage.style.display = 'none';
                    if (tableContainer) tableContainer.style.display = 'block';
                    
                    // Atualizar contador
                    totalCount.textContent = produtoItems.length;
                    return;
                }
                
                // Filtrar produtos
                produtoItems.forEach(item => {
                    const searchData = item.getAttribute('data-search');
                    const matches = searchData.includes(searchTerm);
                    
                    if (matches) {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Atualizar informações da busca
                searchText.textContent = `🔍 Resultados da pesquisa por: "${searchTerm}"`;
                searchCount.textContent = `${visibleCount} produto(s) encontrado(s)`;
                searchInfo.style.display = 'flex';
                totalCount.textContent = visibleCount;
                
                // Mostrar/ocultar mensagem de nenhum resultado
                if (visibleCount === 0) {
                    if (noResultsMessage) noResultsMessage.style.display = 'block';
                    if (tableContainer) tableContainer.style.display = 'none';
                } else {
                    if (noResultsMessage) noResultsMessage.style.display = 'none';
                    if (tableContainer) tableContainer.style.display = 'block';
                }
            }
            
            // Focar no campo de busca quando carregar a página
            setTimeout(() => {
                if (searchInput) searchInput.focus();
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
            
            if (nomeField && !nomeField.value && searchInput && !searchInput.value) {
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
