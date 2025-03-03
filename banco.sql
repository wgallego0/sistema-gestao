-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS sistema_gestao_equipe;
USE sistema_gestao_equipe;

-- Tabela de liderados (funcionários)
CREATE TABLE liderados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cargo VARCHAR(100) NOT NULL,
    cross_funcional BOOLEAN DEFAULT FALSE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de projetos
CREATE TABLE projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    status ENUM('Não iniciado', 'Em andamento', 'Concluído', 'Pausado', 'Cancelado') DEFAULT 'Não iniciado',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de relação entre liderados e projetos
CREATE TABLE liderados_projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    liderado_id INT NOT NULL,
    projeto_id INT NOT NULL,
    percentual_dedicacao INT DEFAULT 100,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    FOREIGN KEY (liderado_id) REFERENCES liderados(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    UNIQUE KEY (liderado_id, projeto_id)
);

-- Tabela de atividades
CREATE TABLE atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT,
    projeto_id INT,
    prioridade ENUM('Alta', 'Média', 'Baixa') DEFAULT 'Média',
    status ENUM('Não iniciada', 'Em andamento', 'Concluída', 'Bloqueada') DEFAULT 'Não iniciada',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_inicio DATE,
    data_fim DATE,
    horas_estimadas DECIMAL(6,2) DEFAULT 0,
    horas_realizadas DECIMAL(6,2) DEFAULT 0,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL
);

-- Tabela de responsáveis por atividades
CREATE TABLE atividades_responsaveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atividade_id INT NOT NULL,
    liderado_id INT NOT NULL,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
    FOREIGN KEY (liderado_id) REFERENCES liderados(id) ON DELETE CASCADE,
    UNIQUE KEY (atividade_id, liderado_id)
);

-- Tabela de apontamentos de horas
CREATE TABLE apontamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    liderado_id INT NOT NULL,
    projeto_id INT,
    atividade_id INT,
    data DATE NOT NULL,
    quantidade_horas DECIMAL(5,2) NOT NULL,
    descricao TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    opr_id INT,
    FOREIGN KEY (liderado_id) REFERENCES liderados(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE SET NULL
);

-- Tabela de OPRs (OnePageReports)
CREATE TABLE oprs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    liderado_id INT NOT NULL,
    semana VARCHAR(20) NOT NULL,
    data_geracao DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_horas_semana DECIMAL(6,2) DEFAULT 0,
    status ENUM('Rascunho', 'Enviado', 'Aprovado', 'Revisão') DEFAULT 'Rascunho',
    FOREIGN KEY (liderado_id) REFERENCES liderados(id) ON DELETE CASCADE
);

-- Tabela de clientes atendidos no OPR
CREATE TABLE opr_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opr_id INT NOT NULL,
    cliente VARCHAR(100) NOT NULL,
    descricao TEXT,
    FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE CASCADE
);

-- Tabela de atividades realizadas no OPR
CREATE TABLE opr_atividades_realizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opr_id INT NOT NULL,
    atividade_id INT,
    descricao TEXT NOT NULL,
    resultado TEXT,
    FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE SET NULL
);

-- Tabela de próximas atividades no OPR
CREATE TABLE opr_proximas_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opr_id INT NOT NULL,
    descricao TEXT NOT NULL,
    data_limite DATE,
    prioridade ENUM('Alta', 'Média', 'Baixa') DEFAULT 'Média',
    FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE CASCADE
);

-- Tabela de riscos identificados no OPR
CREATE TABLE opr_riscos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opr_id INT NOT NULL,
    descricao TEXT NOT NULL,
    impacto ENUM('Alto', 'Médio', 'Baixo') DEFAULT 'Médio',
    probabilidade ENUM('Alta', 'Média', 'Baixa') DEFAULT 'Média',
    mitigacao TEXT,
    FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE CASCADE
);

-- Tabela de menções de projetos no OPR
CREATE TABLE opr_mencoes_projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opr_id INT NOT NULL,
    projeto_id INT,
    descricao TEXT NOT NULL,
    destaque BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL
);

-- Atualizar campo de referência na tabela de apontamentos para OPR
ALTER TABLE apontamentos 
ADD CONSTRAINT fk_apontamentos_opr
FOREIGN KEY (opr_id) REFERENCES oprs(id) ON DELETE SET NULL;

-- Tabela de configurações do sistema
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descricao TEXT,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações iniciais
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('horas_dia_padrao', '8', 'Quantidade padrão de horas por dia de trabalho'),
('dias_semana_trabalho', '5', 'Quantidade padrão de dias de trabalho por semana'),
('formato_semana_opr', 'SS-AAAA', 'Formato para identificação da semana no OPR (SS-AAAA)');

-- Tabela de logs do sistema
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    acao ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    dados_antigos TEXT,
    dados_novos TEXT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    usuario VARCHAR(100)
);

-- Criação de usuários para o sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'gestor', 'liderado') NOT NULL DEFAULT 'liderado',
    liderado_id INT,
    ultimo_acesso DATETIME,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (liderado_id) REFERENCES liderados(id) ON DELETE CASCADE
);

-- Triggers para atualização automática

-- Trigger para atualizar horas realizadas na atividade após inserção de apontamento
DELIMITER //
CREATE TRIGGER after_apontamento_insert
AFTER INSERT ON apontamentos
FOR EACH ROW
BEGIN
    IF NEW.atividade_id IS NOT NULL THEN
        UPDATE atividades
        SET horas_realizadas = horas_realizadas + NEW.quantidade_horas
        WHERE id = NEW.atividade_id;
    END IF;
    
    -- Atualizar total de horas no OPR se estiver associado
    IF NEW.opr_id IS NOT NULL THEN
        UPDATE oprs
        SET total_horas_semana = total_horas_semana + NEW.quantidade_horas
        WHERE id = NEW.opr_id;
    END IF;
END //
DELIMITER ;

-- Trigger para atualizar horas realizadas na atividade após atualização de apontamento
DELIMITER //
CREATE TRIGGER after_apontamento_update
AFTER UPDATE ON apontamentos
FOR EACH ROW
BEGIN
    -- Atualizar horas na atividade antiga (se existir)
    IF OLD.atividade_id IS NOT NULL THEN
        UPDATE atividades
        SET horas_realizadas = horas_realizadas - OLD.quantidade_horas
        WHERE id = OLD.atividade_id;
    END IF;
    
    -- Atualizar horas na nova atividade (se existir)
    IF NEW.atividade_id IS NOT NULL THEN
        UPDATE atividades
        SET horas_realizadas = horas_realizadas + NEW.quantidade_horas
        WHERE id = NEW.atividade_id;
    END IF;
    
    -- Atualizar total de horas no OPR antigo (se existir)
    IF OLD.opr_id IS NOT NULL THEN
        UPDATE oprs
        SET total_horas_semana = total_horas_semana - OLD.quantidade_horas
        WHERE id = OLD.opr_id;
    END IF;
    
    -- Atualizar total de horas no novo OPR (se existir)
    IF NEW.opr_id IS NOT NULL THEN
        UPDATE oprs
        SET total_horas_semana = total_horas_semana + NEW.quantidade_horas
        WHERE id = NEW.opr_id;
    END IF;
END //
DELIMITER ;

-- Trigger para atualizar horas realizadas na atividade após exclusão de apontamento
DELIMITER //
CREATE TRIGGER after_apontamento_delete
AFTER DELETE ON apontamentos
FOR EACH ROW
BEGIN
    IF OLD.atividade_id IS NOT NULL THEN
        UPDATE atividades
        SET horas_realizadas = horas_realizadas - OLD.quantidade_horas
        WHERE id = OLD.atividade_id;
    END IF;
    
    -- Atualizar total de horas no OPR se estiver associado
    IF OLD.opr_id IS NOT NULL THEN
        UPDATE oprs
        SET total_horas_semana = total_horas_semana - OLD.quantidade_horas
        WHERE id = OLD.opr_id;
    END IF;
END //
DELIMITER ;

-- Índices para melhorar performance
CREATE INDEX idx_liderados_email ON liderados(email);
CREATE INDEX idx_projetos_status ON projetos(status);
CREATE INDEX idx_atividades_status ON atividades(status);
CREATE INDEX idx_atividades_projeto ON atividades(projeto_id);
CREATE INDEX idx_apontamentos_data ON apontamentos(data);
CREATE INDEX idx_apontamentos_liderado ON apontamentos(liderado_id);
CREATE INDEX idx_apontamentos_projeto ON apontamentos(projeto_id);
CREATE INDEX idx_oprs_liderado_semana ON oprs(liderado_id, semana);

-- Inserir dados de exemplo

-- Liderados
INSERT INTO liderados (nome, email, cargo, cross_funcional) VALUES
('João Silva', 'joao.silva@empresa.com', 'Desenvolvedor Full Stack', FALSE),
('Maria Oliveira', 'maria.oliveira@empresa.com', 'Designer UX/UI', FALSE),
('Pedro Santos', 'pedro.santos@empresa.com', 'Analista de Projetos', TRUE),
('Ana Souza', 'ana.souza@empresa.com', 'Desenvolvedora Frontend', FALSE),
('Lucas Mendes', 'lucas.mendes@empresa.com', 'Desenvolvedor Backend', FALSE);

-- Projetos
INSERT INTO projetos (nome, descricao, data_inicio, data_fim, status) VALUES
('Website Corporativo', 'Redesign do website institucional da empresa', '2025-01-10', '2025-04-30', 'Em andamento'),
('Aplicativo Mobile', 'Desenvolvimento de app para clientes', '2025-02-15', '2025-06-30', 'Em andamento'),
('Sistema Interno', 'Sistema para gestão de recursos humanos', '2025-03-01', '2025-07-15', 'Não iniciado');

-- Liderados nos projetos
INSERT INTO liderados_projetos (liderado_id, projeto_id, percentual_dedicacao, data_inicio) VALUES
(1, 1, 70, '2025-01-10'),
(1, 2, 30, '2025-02-15'),
(2, 1, 100, '2025-01-10'),
(4, 1, 50, '2025-01-15'),
(4, 2, 50, '2025-02-15'),
(5, 2, 100, '2025-02-15');

-- Atividades
INSERT INTO atividades (titulo, descricao, projeto_id, prioridade, status, data_inicio, data_fim, horas_estimadas) VALUES
('Design de telas', 'Criação de protótipos de alta fidelidade', 1, 'Alta', 'Em andamento', '2025-01-15', '2025-02-28', 120),
('Desenvolvimento frontend', 'Implementação de componentes React', 1, 'Média', 'Não iniciada', '2025-02-20', '2025-03-30', 160),
('API REST', 'Desenvolvimento de API para o aplicativo', 2, 'Alta', 'Em andamento', '2025-02-20', '2025-04-15', 200),
('Testes automatizados', 'Criação de testes para componentes', 1, 'Média', 'Não iniciada', '2025-03-15', '2025-04-15', 80);

-- Responsáveis pelas atividades
INSERT INTO atividades_responsaveis (atividade_id, liderado_id) VALUES
(1, 2),
(2, 1),
(2, 4),
(3, 5),
(4, 1);

-- Apontamentos de horas
INSERT INTO apontamentos (liderado_id, projeto_id, atividade_id, data, quantidade_horas, descricao) VALUES
(2, 1, 1, '2025-01-20', 8, 'Início do design de telas'),
(2, 1, 1, '2025-01-21', 6, 'Continuação do design de telas'),
(5, 2, 3, '2025-02-20', 8, 'Planejamento da API'),
(5, 2, 3, '2025-02-21', 8, 'Desenvolvimento dos endpoints iniciais'),
(1, 1, 2, '2025-02-22', 4, 'Setup inicial dos componentes React');

-- OPRs
INSERT INTO oprs (liderado_id, semana, status) VALUES
(2, '03-2025', 'Enviado'),
(5, '08-2025', 'Enviado');

-- Clientes atendidos
INSERT INTO opr_clientes (opr_id, cliente, descricao) VALUES
(1, 'Departamento de Marketing', 'Alinhamento de requisitos para design de telas'),
(2, 'Equipe de Produto', 'Definição de APIs para o aplicativo');

-- Atividades realizadas
INSERT INTO opr_atividades_realizadas (opr_id, atividade_id, descricao, resultado) VALUES
(1, 1, 'Design das telas principais', 'Protótipos aprovados pelo cliente'),
(2, 3, 'Desenvolvimento da API de autenticação', 'API funcionando e testada');

-- Próximas atividades
INSERT INTO opr_proximas_atividades (opr_id, descricao, data_limite, prioridade) VALUES
(1, 'Finalizar design de telas secundárias', '2025-02-28', 'Alta'),
(2, 'Implementar endpoints de usuário', '2025-03-15', 'Alta');

-- Riscos identificados
INSERT INTO opr_riscos (opr_id, descricao, impacto, probabilidade, mitigacao) VALUES
(1, 'Atraso na aprovação das telas pelo cliente', 'Alto', 'Média', 'Fazer reunião antecipada para validação incremental'),
(2, 'Integração com serviço externo pode não estar pronta', 'Alto', 'Alta', 'Desenvolver mock para testes enquanto aguarda integração');

-- Menções de projetos
INSERT INTO opr_mencoes_projetos (opr_id, projeto_id, descricao, destaque) VALUES
(1, 1, 'Milestone de design concluída conforme planejado', TRUE),
(2, 2, 'API básica implementada antes do prazo', TRUE);

-- Usuários do sistema
INSERT INTO usuarios (nome, email, senha, tipo, liderado_id) VALUES
('Administrador', 'admin@empresa.com', '$2y$10$abcdefghijklmnopqrstuv', 'admin', NULL),
('João Silva', 'joao.silva@empresa.com', '$2y$10$abcdefghijklmnopqrstuv', 'liderado', 1),
('Maria Oliveira', 'maria.oliveira@empresa.com', '$2y$10$abcdefghijklmnopqrstuv', 'liderado', 2),
('Carlos Gestor', 'carlos.gestor@empresa.com', '$2y$10$abcdefghijklmnopqrstuv', 'gestor', NULL);