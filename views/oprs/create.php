<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="main-content">
        <!-- Sidebar com opções -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Conteúdo principal -->
        <div class="content">
            <div class="card">
                <h2>Novo OnePageReport (OPR)</h2>
                <p>Preencha os dados do seu relatório semanal.</p>
                
                <form id="form-opr" action="<?php echo BASE_URL; ?>/api/oprs.php?action=store" method="POST" data-ajax="true" data-reset="false" data-reload-page="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_PREFIX . 'csrf_token']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="liderado_id">Liderado</label>
                            <?php if (hasPermission('gestor') || hasPermission('admin')): ?>
                                <select name="liderado_id" id="liderado_id" class="form-control" required>
                                    <option value="">Selecione um liderado</option>
                                    <?php foreach ($liderados as $l): ?>
                                        <option value="<?php echo $l['id']; ?>" <?php echo ($l['id'] == $lideradoId) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($l['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($liderado['nome']); ?>" readonly>
                                <input type="hidden" name="liderado_id" value="<?php echo $lideradoId; ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="semana">Semana</label>
                            <input type="text" id="semana" name="semana" class="form-control" required value="<?php echo $semana; ?>">
                            <small class="form-help">Formato: <?php echo getConfig('formato_semana_opr', 'SS-AAAA'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Rascunho">Rascunho</option>
                                <option value="Enviado">Enviado</option>
                                <?php if (hasPermission('admin')): ?>
                                    <option value="Aprovado">Aprovado</option>
                                <?php endif; ?>
                                <option value="Revisão">Revisão</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Clientes Atendidos -->
                    <h3>Clientes Atendidos</h3>
                    <div id="opr-clientes-container">
                        <div class="form-row opr-cliente">
                            <div class="form-group">
                                <label>Cliente</label>
                                <input type="text" class="form-control opr-cliente-nome" name="clientes[0][cliente]" required>
                            </div>
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" class="form-control opr-cliente-descricao" name="clientes[0][descricao]">
                            </div>
                            <button type="button" class="btn btn-danger remove-field" data-target="opr-cliente">Remover</button>
                        </div>
                    </div>
                    <button type="button" id="adicionar-cliente" class="btn btn-sm">+ Adicionar Cliente</button>
                    
                    <!-- Atividades Realizadas -->
                    <h3>Atividades Realizadas</h3>
                    <div id="opr-atividades-container">
                        <div class="form-row opr-atividade">
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" class="form-control opr-atividade-descricao" name="atividades[0][descricao]" required>
                            </div>
                            <div class="form-group">
                                <label>Resultado</label>
                                <input type="text" class="form-control opr-atividade-resultado" name="atividades[0][resultado]">
                            </div>
                            <button type="button" class="btn btn-danger remove-field" data-target="opr-atividade">Remover</button>
                        </div>
                    </div>
                    <button type="button" id="adicionar-atividade" class="btn btn-sm">+ Adicionar Atividade</button>
                    
                    <!-- Próximas Atividades -->
                    <h3>Próximas Atividades</h3>
                    <div id="opr-proximas-container">
                        <div class="form-row opr-proxima">
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" class="form-control opr-proxima-descricao" name="proximas[0][descricao]" required>
                            </div>
                            <div class="form-group">
                                <label>Data Limite</label>
                                <input type="date" class="form-control opr-proxima-data" name="proximas[0][data_limite]">
                            </div>
                            <div class="form-group">
                                <label>Prioridade</label>
                                <select class="form-control opr-proxima-prioridade" name="proximas[0][prioridade]">
                                    <option value="Alta">Alta</option>
                                    <option value="Média" selected>Média</option>
                                    <option value="Baixa">Baixa</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-danger remove-field" data-target="opr-proxima">Remover</button>
                        </div>
                    </div>
                    <button type="button" id="adicionar-proxima" class="btn btn-sm">+ Adicionar Próxima Atividade</button>
                    
                    <!-- Riscos Identificados -->
                    <h3>Riscos Identificados</h3>
                    <div id="opr-riscos-container">
                        <div class="form-row opr-risco">
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" class="form-control opr-risco-descricao" name="riscos[0][descricao]" required>
                            </div>
                            <div class="form-group">
                                <label>Impacto</label>
                                <select class="form-control opr-risco-impacto" name="riscos[0][impacto]">
                                    <option value="Alto">Alto</option>
                                    <option value="Médio" selected>Médio</option>
                                    <option value="Baixo">Baixo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Probabilidade</label>
                                <select class="form-control opr-risco-probabilidade" name="riscos[0][probabilidade]">
                                    <option value="Alta">Alta</option>
                                    <option value="Média" selected>Média</option>
                                    <option value="Baixa">Baixa</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mitigação</label>
                                <input type="text" class="form-control opr-risco-mitigacao" name="riscos[0][mitigacao]">
                            </div>
                            <button type="button" class="btn btn-danger remove-field" data-target="opr-risco">Remover</button>
                        </div>
                    </div>
                    <button type="button" id="adicionar-risco" class="btn btn-sm">+ Adicionar Risco</button>
                    
                    <!-- Menções de Projetos -->
                    <h3>Menções de Projetos</h3>
                    <div id="opr-mencoes-container">
                        <div class="form-row opr-mencao">
                            <div class="form-group">
                                <label>Projeto</label>
                                <select class="form-control opr-mencao-projeto" name="mencoes[0][projeto_id]">
                                    <option value="">Selecione um projeto (opcional)</option>
                                    <?php foreach ($projetos as $projeto): ?>
                                        <option value="<?php echo $projeto['projeto_id']; ?>">
                                            <?php echo htmlspecialchars($projeto['projeto_nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Descrição</label>
                                <input type="text" class="form-control opr-mencao-descricao" name="mencoes[0][descricao]" required>
                            </div>
                            <div class="form-group">
                                <label></label>
                                <div class="form-check">
                                    <input type="checkbox" class="opr-mencao-destaque" name="mencoes[0][destaque]" id="destaque-0">
                                    <label for="destaque-0">Destaque</label>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger remove-field" data-target="opr-mencao">Remover</button>
                        </div>
                    </div>
                    <button type="button" id="adicionar-mencao" class="btn btn-sm">+ Adicionar Menção</button>
                    
                    <!-- Apontamentos da Semana -->
                    <h3>Apontamentos da Semana</h3>
                    <?php if (empty($apontamentosSemana)): ?>
                        <p class="empty-message">Não há apontamentos registrados para esta semana</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Incluir</th>
                                    <th>Data</th>
                                    <th>Projeto</th>
                                    <th>Atividade</th>
                                    <th>Horas</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $index = 0; foreach ($apontamentosSemana as $apontamento): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="apontamentos[<?php echo $index; ?>][incluir]" id="apontamento-<?php echo $index; ?>" checked>
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][id]" value="<?php echo $apontamento['id']; ?>">
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][data]" value="<?php echo $apontamento['data']; ?>">
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][projeto_id]" value="<?php echo $apontamento['projeto_id']; ?>">
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][atividade_id]" value="<?php echo $apontamento['atividade_id']; ?>">
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][quantidade_horas]" value="<?php echo $apontamento['quantidade_horas']; ?>">
                                            <input type="hidden" name="apontamentos[<?php echo $index; ?>][descricao]" value="<?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?>">
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($apontamento['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['projeto_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($apontamento['atividade_titulo'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($apontamento['quantidade_horas'], 1); ?>h</td>
                                        <td><?php echo htmlspecialchars($apontamento['descricao'] ?? ''); ?></td>
                                    </tr>
                                <?php $index++; endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">Total:</th>
                                    <th>
                                        <?php 
                                            $totalHoras = array_reduce($apontamentosSemana, function($total, $apontamento) {
                                                return $total + $apontamento['quantidade_horas'];
                                            }, 0);
                                            echo number_format($totalHoras, 1); 
                                        ?>h
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Salvar OPR</button>
                        <a href="<?php echo BASE_URL; ?>/oprs.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>