<?php
// ============================================================
// CONFIGURAÇÕES DO SISTEMA
// Railway: use variáveis de ambiente no painel do Railway
// Hostinger: edite os valores padrão abaixo
// ============================================================

// Banco de dados
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u106692717_bancoshild');
define('DB_USER', getenv('DB_USER') ?: 'u106692717_metashilcria');
define('DB_PASS', getenv('DB_PASS') ?: 'senhaBanco123#');

// URL principal do seu sistema (sem barra no final)
define('BASE_URL', getenv('BASE_URL') ?: 'https://gerasite.camillefournier.shop');

// Sufixo dos subdomínios gerados (com ponto no início)
// Exemplo: empresa.gerasite.camillefournier.shop
define('SUBDOMAIN_SUFFIX', getenv('SUBDOMAIN_SUFFIX') ?: '.gerasite.camillefournier.shop');

// IP do servidor (necessário para instruções de DNS)
define('SERVER_IP', getenv('SERVER_IP') ?: '147.79.84.172');

// Versão e nome do sistema
define('SYSTEM_NAME', 'Meta Shield');
define('SYSTEM_VERSION', '1.0');


