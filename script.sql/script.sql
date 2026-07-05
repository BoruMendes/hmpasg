
CREATE DATABASE IF NOT EXISTS hmpasg;
USE hmpasg;

CREATE TABLE utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'medico', 'enfermeiro', 'recepcao') NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    data_nasc DATE NOT NULL,
    genero ENUM('M', 'F') NOT NULL,
    posto_militar VARCHAR(50),
    unidade VARCHAR(100),
    contacto VARCHAR(20),
    id_utilizador_criador INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizador_criador) REFERENCES utilizadores(id) ON DELETE SET NULL
);

CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT UNIQUE,
    especialidade VARCHAR(100) NOT NULL,
    crm VARCHAR(20) NOT NULL UNIQUE,
    horario_atendimento VARCHAR(100),
    FOREIGN KEY (id_utilizador) REFERENCES utilizadores(id) ON DELETE CASCADE
);

CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico INT NOT NULL,
    data_hora DATETIME NOT NULL,
    motivo TEXT,
    status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
    observacoes TEXT,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medico) REFERENCES medicos(id) ON DELETE CASCADE
);

CREATE TABLE internamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    data_entrada DATE NOT NULL,
    data_saida_prevista DATE,
    data_saida_real DATE,
    leito VARCHAR(20),
    diagnostico_inicial TEXT,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    principio_ativo VARCHAR(100),
    dosagem VARCHAR(50),
    quantidade_stock INT NOT NULL DEFAULT 0
);

CREATE TABLE prescricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_consulta INT NULL,
    id_internamento INT NULL,
    id_medicamento INT NOT NULL,
    quantidade_por_tomada VARCHAR(50),
    frequencia VARCHAR(50),
    duracao_dias INT,
    FOREIGN KEY (id_consulta) REFERENCES consultas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_internamento) REFERENCES internamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medicamento) REFERENCES medicamentos(id)
);

CREATE TABLE exames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    tipo_exame VARCHAR(100) NOT NULL,
    data_solicitacao DATE NOT NULL,
    data_resultado DATE,
    resultado_arquivo VARCHAR(255),
    id_medico_solicitante INT NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medico_solicitante) REFERENCES medicos(id)
);

INSERT INTO utilizadores (nome, email, senha_hash, tipo) VALUES 
('Administrador', 'admin@hmpasg.com', '$2y$10$AWmKUdMnl4IouysLZX7CIO/KBqALOa0uIHrlRK2vo4P/aOz7.Pjq6', 'admin'),
('Dr. Martinho Mendes', 'martinho@hmpasg.com', '$2y$10$AWmKUdMnl4IouysLZX7CIO/KBqALOa0uIHrlRK2vo4P/aOz7.Pjq6', 'medico'),
('Enf. Nelcia Mendes', 'nelcia@hmpasg.com', '$2y$10$AWmKUdMnl4IouysLZX7CIO/KBqALOa0uIHrlRK2vo4P/aOz7.Pjq6', 'enfermeira'),
('Recepcao Pedro', 'pedro@hmpasg.com', '$2y$10$AWmKUdMnl4IouysLZX7CIO/KBqALOa0uIHrlRK2vo4P/aOz7.Pjq6', 'recepcao');

INSERT INTO medicos (id_utilizador, especialidade, crm, horario_atendimento) VALUES 
(2, 'Clínica Geral', 'CRM-12345', 'Seg-Sex 08h-12h');

INSERT INTO pacientes (nome, data_nasc, genero, posto_militar, unidade, contacto, id_utilizador_criador) VALUES
('Ború Mendes', '2006-07-11', 'M', 'Soldado', 'Batalhão 1', '956897622', 1);

INSERT INTO medicamentos (nome, principio_ativo, dosagem, quantidade_stock) VALUES
('Paracetamol', 'Paracetamol', '500mg', 100),
('Ibuprofeno', 'Ibuprofeno', '400mg', 50);