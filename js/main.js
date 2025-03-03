/**
 * Scripts principais do Sistema de Gestão de Equipes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes da interface
    initNavigation();
    initTabs();
    initModals();
    initForms();
    initCharts();
    initNotifications();
    
    // Carregar funcionalidades específicas de cada página
    loadPageSpecificFunctions();
});

/**
 * Inicializar navegação principal
 */
function initNavigation() {
    // Navegação principal
    const navLinks = document.querySelectorAll('.navbar a');
    navLinks.forEach(link => {
        // Remover o event listener que estava impedindo a navegação normal
        // Agora os links funcionarão diretamente como links HTML padrão
        if (link.getAttribute('href').indexOf('logout.php') === -1) {
            link.addEventListener('click', function() {
                // Somente remover classe ativa e adicionar ao clicado
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        }
    });
    
    // Links de ação rápida na sidebar agora são links diretos HTML em vez de usar data-action
    // Continuamos a manter compatibilidade com o código existente
    const actionLinks = document.querySelectorAll('[data-action]');
    actionLinks.forEach(link => {
        link.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            handleAction(action);
        });
    });
}

/**
 * Mostrar página específica
 */
function showPage(pageId) {
    // Esta função não é mais utilizada para navegação
    console.log('showPage foi chamado com ID:', pageId);
}

/**
 * Manipular ações rápidas
 */
function handleAction(action) {
    // A função handleAction é mantida para retrocompatibilidade,
    // mas a maioria dos links agora são diretamente HTML
    switch (action) {
        case 'associar_projeto':
            // Abrir modal para associar liderado a projeto
            const modal = document.getElementById('modal-associar');
            if (modal) {
                modal.style.display = 'flex';
            }
            break;
        default:
            console.warn('Ação não implementada ou obsoleta: ' + action);
    }
}

/**
 * Inicializar sistema de tabs
 */
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            const tabContainer = this.closest('.tab-container');
            
            // Remover classe ativa de todos os botões e conteúdos
            tabContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            tabContainer.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Adicionar classe ativa ao botão e conteúdo selecionados
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

/**
 * Inicializar modais
 */
function initModals() {
    // Abrir modal
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.style.display = 'flex';
                
                // Preencher dados específicos do modal se necessário
                if (this.hasAttribute('data-id')) {
                    const id = this.getAttribute('data-id');
                    fillModalData(modalId, id);
                }
            }
        });
    });
    
    // Fechar modal
    const closeButtons = document.querySelectorAll('.modal-close, [data-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Fechar modal ao clicar fora
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

/**
 * Preencher dados em um modal
 */
function fillModalData(modalId, id) {
    switch (modalId) {
        case 'modal-associar':
            // Preencher formulário de associação com ID do liderado
            document.getElementById('associar-liderado-id').value = id;
            
            // Carregar projetos disponíveis para associação
            loadProjetosParaAssociacao(id);
            break;
        
        case 'modal-opr':
            // Carregar detalhes do OPR para visualização
            loadOPRDetails(id);
            break;
            
        default:
            console.warn('Preenchimento de modal não implementado: ' + modalId);
    }
}

/**
 * Carregar projetos disponíveis para associação
 */
function loadProjetosParaAssociacao(lideradoId) {
    const selectProjeto = document.getElementById('associar-projeto');
    
    // Limpar opções atuais
    selectProjeto.innerHTML = '<option value="">Selecione um projeto</option>';
    
    // Fazer requisição AJAX para obter projetos
    fetch(BASE_URL + '/api/projetos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Adicionar opções de projetos
                data.data.forEach(projeto => {
                    const option = document.createElement('option');
                    option.value = projeto.id;
                    option.textContent = projeto.nome;
                    selectProjeto.appendChild(option);
                });
            } else {
                showNotification(data.error || 'Erro ao carregar projetos', 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar projetos:', error);
            showNotification('Erro de comunicação com o servidor', 'error');
        });
}

/**
 * Carregar detalhes do OPR para visualização
 */
function loadOPRDetails(oprId) {
    const modalContent = document.getElementById('modal-opr-conteudo');
    
    // Mostrar indicador de carregamento
    modalContent.innerHTML = '<div class="loading">Carregando...</div>';
    
    // Fazer requisição AJAX para obter detalhes do OPR
    fetch(BASE_URL + '/api/oprs.php?id=' + oprId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Preencher conteúdo do modal com os dados do OPR
                modalContent.innerHTML = renderOPRView(data.data);
                
                // Inicializar gráficos se houver
                initOPRCharts(data.data.graficos_formatados);
            } else {
                modalContent.innerHTML = '<div class="error">' + (data.error || 'Erro ao carregar detalhes do OPR') + '</div>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do OPR:', error);
            modalContent.innerHTML = '<div class="error">Erro de comunicação com o servidor</div>';
        });
}

/**
 * Renderizar visualização do OPR
 */
function renderOPRView(opr) {
    // Função para renderizar HTML do OPR
    // Este é um template simples, poderia ser mais complexo
    let html = `
        <div class="opr-view">
            <div class="opr-header">
                <h2>OPR - ${opr.semana}</h2>
                <div>
                    <p>Liderado: <strong>${opr.liderado_nome}</strong></p>
                    <p>Status: <span class="badge ${opr.status_classe}">${opr.status}</span></p>
                </div>
            </div>
            
            <div class="metrics">
                <div class="metric-box">
                    <div class="metric-value">${opr.total_horas_semana}</div>
                    <div class="metric-label">Horas Totais</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${opr.total_geral.clientes}</div>
                    <div class="metric-label">Clientes</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${opr.total_geral.atividades}</div>
                    <div class="metric-label">Atividades</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${opr.total_geral.proximas}</div>
                    <div class="metric-label">Próximas</div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>Horas por Projeto</h3>
                <canvas id="chart-projetos"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Horas por Dia</h3>
                <canvas id="chart-dias"></canvas>
            </div>
    `;
    
    // Adicionar seções de clientes, atividades, etc.
    if (opr.clientes && opr.clientes.length > 0) {
        html += '<h3>Clientes Atendidos</h3><ul>';
        opr.clientes.forEach(cliente => {
            html += `<li><strong>${cliente.cliente}</strong>: ${cliente.descricao || ''}</li>`;
        });
        html += '</ul>';
    }
    
    if (opr.atividades_realizadas && opr.atividades_realizadas.length > 0) {
        html += '<h3>Atividades Realizadas</h3><ul>';
        opr.atividades_realizadas.forEach(atividade => {
            html += `<li><strong>${atividade.descricao}</strong>`;
            if (atividade.resultado) {
                html += `<br>Resultado: ${atividade.resultado}`;
            }
            html += '</li>';
        });
        html += '</ul>';
    }
    
    if (opr.proximas_atividades && opr.proximas_atividades.length > 0) {
        html += '<h3>Próximas Atividades</h3><ul>';
        opr.proximas_atividades.forEach(proxima => {
            html += `<li><strong>${proxima.descricao}</strong>`;
            if (proxima.data_limite) {
                html += `<br>Data Limite: ${formatDate(proxima.data_limite)}`;
            }
            html += `<br>Prioridade: ${proxima.prioridade}`;
            html += '</li>';
        });
        html += '</ul>';
    }
    
    if (opr.riscos && opr.riscos.length > 0) {
        html += '<h3>Riscos Identificados</h3><ul>';
        opr.riscos.forEach(risco => {
            const riskClass = risco.impacto === 'Alto' ? 'risk-high' : 
                             (risco.impacto === 'Médio' ? 'risk-medium' : 'risk-low');
            
            html += `<li class="${riskClass}"><strong>${risco.descricao}</strong>`;
            html += `<br>Impacto: ${risco.impacto}, Probabilidade: ${risco.probabilidade}`;
            if (risco.mitigacao) {
                html += `<br>Mitigação: ${risco.mitigacao}`;
            }
            html += '</li>';
        });
        html += '</ul>';
    }
    
    html += '</div>';
    
    return html;
}

/**
 * Inicializar gráficos do OPR
 */
function initOPRCharts(graficos) {
    if (!graficos) return;
    
    // Gráfico de horas por projeto
    if (graficos.projetos && document.getElementById('chart-projetos')) {
        new Chart(document.getElementById('chart-projetos'), {
            type: 'bar',
            data: {
                labels: graficos.projetos.labels,
                datasets: [{
                    label: 'Horas',
                    data: graficos.projetos.data,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Gráfico de horas por dia
    if (graficos.dias && document.getElementById('chart-dias')) {
        new Chart(document.getElementById('chart-dias'), {
            type: 'bar',
            data: {
                labels: graficos.dias.labels,
                datasets: [{
                    label: 'Horas',
                    data: graficos.dias.data,
                    backgroundColor: '#2ecc71',
                    borderColor: '#27ae60',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * Inicializar formulários
 */
function initForms() {
    // Formulários de submit AJAX
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.getAttribute('action') || window.location.href;
            const method = this.getAttribute('method') || 'POST';
            
            // Fazer requisição AJAX
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensagem de sucesso
                    showNotification(data.message || 'Operação realizada com sucesso', 'success');
                    
                    // Executar callback se especificado
                    if (this.hasAttribute('data-callback')) {
                        const callback = this.getAttribute('data-callback');
                        if (typeof window[callback] === 'function') {
                            window[callback](data);
                        }
                    }
                    
                    // Redirecionar se especificado
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                    
                    // Resetar formulário se necessário
                    if (this.hasAttribute('data-reset') && this.getAttribute('data-reset') !== 'false') {
                        this.reset();
                    }
                    
                    // Fechar modal se dentro de um
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    
                    // Recarregar tabela de dados se especificado
                    if (this.hasAttribute('data-reload-table')) {
                        const tableId = this.getAttribute('data-reload-table');
                        reloadTable(tableId);
                    }
                    
                    // Recarregar página se especificado
                    if (this.hasAttribute('data-reload-page') && this.getAttribute('data-reload-page') !== 'false') {
                        window.location.reload();
                    }
                } else {
                    // Mostrar mensagem de erro
                    showNotification(data.error || 'Erro ao processar requisição', 'error');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                showNotification('Erro de comunicação com o servidor', 'error');
            });
        });
    });
    
    // Botões de exclusão
    const deleteButtons = document.querySelectorAll('[data-delete]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const entity = this.getAttribute('data-delete');
            const confirmMsg = this.getAttribute('data-confirm') || `Tem certeza que deseja excluir este ${entity}?`;
            
            if (confirm(confirmMsg)) {
                deleteEntity(entity, id, this);
            }
        });
    });
    
    // Adicionar campos dinâmicos (para OPR)
    setupDynamicFields();
}

/**
 * Configurar campos dinâmicos para o OPR
 */
function setupDynamicFields() {
    // Adicionar cliente
    const addClienteBtn = document.getElementById('adicionar-cliente');
    if (addClienteBtn) {
        addClienteBtn.addEventListener('click', function() {
            const container = document.getElementById('opr-clientes-container');
            const index = container.querySelectorAll('.opr-cliente').length;
            
            const html = `
                <div class="form-row opr-cliente">
                    <div class="form-group">
                        <label>Cliente</label>
                        <input type="text" class="form-control opr-cliente-nome" name="clientes[${index}][cliente]" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" class="form-control opr-cliente-descricao" name="clientes[${index}][descricao]">
                    </div>
                    <button type="button" class="btn btn-danger remove-field" data-target="opr-cliente">Remover</button>
                </div>
            `;
            
            // Adicionar HTML
            container.insertAdjacentHTML('beforeend', html);
            
            // Atualizar handlers de remoção
            updateRemoveFieldHandlers();
        });
    }
    
    // Adicionar atividade
    const addAtividadeBtn = document.getElementById('adicionar-atividade');
    if (addAtividadeBtn) {
        addAtividadeBtn.addEventListener('click', function() {
            const container = document.getElementById('opr-atividades-container');
            const index = container.querySelectorAll('.opr-atividade').length;
            
            const html = `
                <div class="form-row opr-atividade">
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" class="form-control opr-atividade-descricao" name="atividades[${index}][descricao]" required>
                    </div>
                    <div class="form-group">
                        <label>Resultado</label>
                        <input type="text" class="form-control opr-atividade-resultado" name="atividades[${index}][resultado]">
                    </div>
                    <button type="button" class="btn btn-danger remove-field" data-target="opr-atividade">Remover</button>
                </div>
            `;
            
            // Adicionar HTML
            container.insertAdjacentHTML('beforeend', html);
            
            // Atualizar handlers de remoção
            updateRemoveFieldHandlers();
        });
    }
    
    // Adicionar próxima atividade
    const addProximaBtn = document.getElementById('adicionar-proxima');
    if (addProximaBtn) {
        addProximaBtn.addEventListener('click', function() {
            const container = document.getElementById('opr-proximas-container');
            const index = container.querySelectorAll('.opr-proxima').length;
            
            const html = `
                <div class="form-row opr-proxima">
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" class="form-control opr-proxima-descricao" name="proximas[${index}][descricao]" required>
                    </div>
                    <div class="form-group">
                        <label>Data Limite</label>
                        <input type="date" class="form-control opr-proxima-data" name="proximas[${index}][data_limite]">
                    </div>
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select class="form-control opr-proxima-prioridade" name="proximas[${index}][prioridade]">
                            <option value="Alta">Alta</option>
                            <option value="Média" selected>Média</option>
                            <option value="Baixa">Baixa</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-danger remove-field" data-target="opr-proxima">Remover</button>
                </div>
            `;
            
            // Adicionar HTML
            container.insertAdjacentHTML('beforeend', html);
            
            // Atualizar handlers de remoção
            updateRemoveFieldHandlers();
        });
    }
    
    // Adicionar risco
    const addRiscoBtn = document.getElementById('adicionar-risco');
    if (addRiscoBtn) {
        addRiscoBtn.addEventListener('click', function() {
            const container = document.getElementById('opr-riscos-container');
            const index = container.querySelectorAll('.opr-risco').length;
            
            const html = `
                <div class="form-row opr-risco">
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" class="form-control opr-risco-descricao" name="riscos[${index}][descricao]" required>
                    </div>
                    <div class="form-group">
                        <label>Impacto</label>
                        <select class="form-control opr-risco-impacto" name="riscos[${index}][impacto]">
                            <option value="Alto">Alto</option>
                            <option value="Médio" selected>Médio</option>
                            <option value="Baixo">Baixo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mitigação</label>
                        <input type="text" class="form-control opr-risco-mitigacao" name="riscos[${index}][mitigacao]">
                    </div>
                    <button type="button" class="btn btn-danger remove-field" data-target="opr-risco">Remover</button>
                </div>
            `;
            
            // Adicionar HTML
            container.insertAdjacentHTML('beforeend', html);
            
            // Atualizar handlers de remoção
            updateRemoveFieldHandlers();
        });
    }
    
    // Inicializar handlers de remoção
    updateRemoveFieldHandlers();
}

/**
 * Atualizar handlers de remoção de campos
 */
function updateRemoveFieldHandlers() {
    const removeButtons = document.querySelectorAll('.remove-field');
    
    removeButtons.forEach(button => {
        // Remover handler antigo para evitar duplicação
        button.removeEventListener('click', handleRemoveField);
        
        // Adicionar novo handler
        button.addEventListener('click', handleRemoveField);
    });
}

/**
 * Manipulador de evento para remover campo
 */
function handleRemoveField() {
    const targetClass = this.getAttribute('data-target');
    const row = this.closest('.' + targetClass);
    
    if (row) {
        row.remove();
    }
}

/**
 * Excluir entidade
 */
function deleteEntity(entity, id, button) {
    // Configurar URL e dados
    const url = BASE_URL + '/api/' + entity + '.php';
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');
    formData.append('csrf_token', CSRF_TOKEN);
    
    // Fazer requisição AJAX
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            showNotification(data.message || 'Item excluído com sucesso', 'success');
            
            // Remover linha da tabela se especificado
            if (button.hasAttribute('data-table-row')) {
                const row = button.closest('tr');
                if (row) {
                    row.remove();
                }
            } else {
                // Recarregar página
                window.location.reload();
            }
        } else {
            // Mostrar mensagem de erro
            showNotification(data.error || 'Erro ao excluir item', 'error');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showNotification('Erro de comunicação com o servidor', 'error');
    });
}

/**
 * Recarregar tabela de dados
 */
function reloadTable(tableId) {
    const table = document.getElementById(tableId);
    
    if (!table) return;
    
    // Obter URL de dados
    const dataUrl = table.getAttribute('data-url');
    
    if (!dataUrl) return;
    
    // Mostrar indicador de carregamento
    const tbody = table.querySelector('tbody');
    tbody.innerHTML = '<tr><td colspan="100%">Carregando...</td></tr>';
    
    // Fazer requisição AJAX
    fetch(dataUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Renderizar linhas da tabela
            renderTableRows(tableId, data.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="100%">Erro ao carregar dados: ' + (data.error || 'Erro desconhecido') + '</td></tr>';
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        tbody.innerHTML = '<tr><td colspan="100%">Erro de comunicação com o servidor</td></tr>';
    });
}

/**
 * Renderizar linhas da tabela
 */
function renderTableRows(tableId, data) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    
    if (!table || !tbody || !data) return;
    
    // Limpar tabela
    tbody.innerHTML = '';
    
    // Verificar se há dados
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100%">Nenhum registro encontrado</td></tr>';
        return;
    }
    
    // Obter função de renderização específica
    const renderFunction = getTableRenderFunction(tableId);
    
    // Renderizar cada linha
    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = renderFunction(item);
        tbody.appendChild(row);
    });
    
    // Inicializar botões na tabela
    initTableButtons();
}

/**
 * Obter função de renderização específica para a tabela
 */
function getTableRenderFunction(tableId) {
    // Funções de renderização específicas para cada tabela
    const renderFunctions = {
        'tabela-liderados': renderLideradoRow,
        'tabela-projetos': renderProjetoRow,
        'tabela-atividades': renderAtividadeRow,
        'tabela-oprs': renderOPRRow,
        'tabela-apontamentos': renderApontamentoRow
        // Adicionar mais conforme necessário
    };
    
    // Retornar função específica ou função genérica
    return renderFunctions[tableId] || renderGenericRow;
}

/**
 * Função genérica de renderização de linha
 */
function renderGenericRow(item) {
    let html = '';
    
    // Criar células para cada propriedade
    for (const prop in item) {
        if (Object.prototype.hasOwnProperty.call(item, prop)) {
            html += `<td>${item[prop]}</td>`;
        }
    }
    
    return html;
}

/**
 * Funções específicas de renderização de linha
 */
// Implementar conforme necessário (função para liderados, projetos, etc.)

/**
 * Inicializar botões nas tabelas
 */
function initTableButtons() {
    // Botões de visualização
    const viewButtons = document.querySelectorAll('[data-view]');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const entity = this.getAttribute('data-view');
            const id = this.getAttribute('data-id');
            window.location.href = BASE_URL + '/' + entity + '.php?action=view&id=' + id;
        });
    });
    
    // Botões de edição
    const editButtons = document.querySelectorAll('[data-edit]');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const entity = this.getAttribute('data-edit');
            const id = this.getAttribute('data-id');
            window.location.href = BASE_URL + '/' + entity + '.php?action=edit&id=' + id;
        });
    });
    
    // Botões de exclusão
    initForms(); // Reinicializar para pegar novos botões de exclusão
}

/**
 * Inicializar gráficos
 */
function initCharts() {
    // Inicializar gráficos específicos da página atual
    // (implementados em scripts específicos de cada página)
}

/**
 * Inicializar sistema de notificações
 */
function initNotifications() {
    // Verificar se há mensagem de erro na sessão
    const errorMsg = SESSION_ERROR;
    if (errorMsg) {
        showNotification(errorMsg, 'error');
    }
    
    // Verificar se há mensagem de sucesso na sessão
    const successMsg = SESSION_SUCCESS;
    if (successMsg) {
        showNotification(successMsg, 'success');
    }
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    
    if (!notification) return;
    
    // Definir classe com base no tipo
    notification.className = 'notification';
    notification.classList.add('notification-' + type);
    
    // Definir mensagem
    notification.textContent = message;
    
    // Mostrar notificação
    notification.style.display = 'block';
    
    // Esconder após 5 segundos
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

/**
 * Carregar funcionalidades específicas de cada página
 */
function loadPageSpecificFunctions() {
    // Verificar URL atual para determinar página
    const path = window.location.pathname;
    const page = path.substring(path.lastIndexOf('/') + 1).replace('.php', '');
    
    // Carregar script específico se existir função
    if (typeof window['init' + capitalize(page) + 'Page'] === 'function') {
        window['init' + capitalize(page) + 'Page']();
    }
}

/**
 * Funções auxiliares
 */

/**
 * Formatar data
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

/**
 * Primeira letra maiúscula
 */
function capitalize(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}