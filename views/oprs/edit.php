<button type="button" id="adicionar-proxima" class="btn btn-sm">+ Adicionar Próxima Atividade</button>
                    
                    <!-- Riscos Identificados -->
                    <h3>Riscos Identificados</h3>
                    <div id="opr-riscos-container">
                        <?php if (empty($opr['riscos'])): ?>
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
                        <?php else: ?>
                            <?php foreach ($opr['riscos'] as $index => $risco): ?>
                                <div class="form-row opr-risco">
                                    <div class="form-group">
                                        <label>Descrição</label>
                                        <input type="text" class="form-control opr-risco-descricao" name="riscos[<?php echo $index; ?>][descricao]" value="<?php echo htmlspecialchars($risco['descricao']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Impacto</label>
                                        <select class="form-control opr-risco-impacto" name="riscos[<?php echo $index; ?>][impacto]">
                                            <option value="Alto" <?php echo $risco['impacto'] === 'Alto' ? 'selected' : ''; ?>>Alto</option>
                                            <option value="Médio" <?php echo $risco['impacto'] === 'Médio' ? 'selected' : ''; ?>>Médio</option>
                                            <option value="Baixo" <?php echo $risco['impacto'] === 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Probabilidade</label>
                                        <select class="form-control opr-risco-probabilidade" name="riscos[<?php echo $index; ?>][probabilidade]">
                                            <option value="Alta" <?php echo $risco['probabilidade'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                            <option value="Média" <?php echo $risco['probabilidade'] === 'Média' ? 'selected' : ''; ?>>Média</option>
                                            <option value="Baixa" <?php echo $risco['probabilidade'] === 'Baixa' ? 'selected' : ''; ?>>Baixa</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Mitigação</label>
                                        <input type="text" class="form-control opr-risco-mitigacao" name="riscos[<?php echo $index; ?>][mitigacao]" value="<?php echo htmlspecialchars($risco['mitigacao'] ?? ''); ?>">
                                    </div>
                                    <button type="button" class="btn btn-danger remove-field" data-target="opr-risco">Remover</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="adicionar-risco" class="btn btn-sm">+ Adicionar Risco</button>
                    
                    <!-- Menções de Projetos -->
                    <h3>Menções de Projetos</h3>
                    <div id="opr-mencoes-container">
                        <?php if (empty($opr['mencoes_projetos'])): ?>
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
                        <?php else: ?>
                            <?php foreach ($opr['mencoes_projetos'] as $index => $mencao): ?>
                                <div class="form-row opr-mencao">
                                    <div class="form-group">
                                        <label>Projeto</label>
                                        <select class="form-control opr-mencao-projeto" name="mencoes[<?php echo $index; ?>][projeto_id]">
                                            <option value="">Selecione um projeto (opcional)</option>
                                            <?php foreach ($projetos as $projeto): ?>
                                                <option value="<?php echo $projeto['projeto_id']; ?>" <?php echo ($mencao['projeto_id'] == $projeto['projeto_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($projeto['projeto_nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Descrição</label>
                                        <input type="text" class="form-control opr-mencao-descricao" name="mencoes[<?php echo $index; ?>][descricao]" value="<?php echo htmlspecialchars($mencao['descricao']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label></label>
                                        <div class="form-check">
                                            <input type="checkbox" class="opr-mencao-destaque" name="mencoes[<?php echo $index; ?>][destaque]" id="destaque-<?php echo $index; ?>" <?php echo $mencao['destaque'] ? 'checked' : ''; ?>>
                                            <label for="destaque-<?php echo $index; ?>">Destaque</label>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-danger remove-field" data-target="opr-mencao">Remover</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="adicionar-mencao" class="btn btn-sm">+ Adicionar Menção</button>
                    
                    <!-- Apontamentos da Semana -->
                    <h3>Apontamentos da Semana</h3>
                    <?php if (empty($opr['apontamentos'])): ?>
                        <p class="empty-message">Não há apontamentos registrados para este OPR</p>
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
                                <?php $index = 0; foreach ($opr['apontamentos'] as $apontamento): ?>
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
                                    <th><?php echo number_format($opr['total_horas_semana'], 1); ?>h</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Atualizar OPR</button>
                        <a href="<?php echo BASE_URL; ?>/oprs.php?action=view&id=<?php echo $opr['id']; ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>