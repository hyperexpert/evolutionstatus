<?php
/**
 * Plugin Name:       Status da Instância - Evolution API
 * Description:       Exibe o status de uma instância da Evolution API (v2.0 ou superior), e o QR Code caso esteja desconectado. Use os shortcodes: <code>[evolution_config]</code> para a página de configuração e <code>[evolution_status]</code> para exibir o painel.
 * Version:           2.2.3
 * Author:            Daniel Jardim | Expert360
 * Author URI:        https://expert360.com.br
 * License:           GPLv2 or later
 * Text Domain:       evolution-status
 */

// Previne o acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Função que registra os shortcodes do plugin.
 */
function ev_register_shortcodes() {
    add_shortcode( 'evolution_config', 'ev_render_config_shortcode' );
    add_shortcode( 'evolution_status', 'ev_render_status_shortcode' );
}
add_action( 'init', 'ev_register_shortcodes' );

/**
 * Renderiza o shortcode [evolution_config].
 * Exibe o formulário para salvar as credenciais da API.
 */
function ev_render_config_shortcode() {
    // Usamos ob_start e ob_get_clean para capturar o HTML em uma variável
    ob_start();
    ?>
    <style>
        /* Estilos do formulário de configuração */
        .api-config-container {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --text-color: #333;
            --label-color: #555;
            --border-color: #ced4da;
            --focus-glow-color: rgba(0, 123, 255, 0.25);
            width: 100%; padding: 15px; box-sizing: border-box; font-family: inherit;
        }
        .api-config-container h2 { color: var(--text-color); text-align: center; margin-top: 0; margin-bottom: 30px; font-weight: 700; }
        .api-config-container .form-group { margin-bottom: 25px; }
        .api-config-container label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--label-color); }
        .api-config-container input[type="text"], .api-config-container input[type="password"] {
            width: 100%; padding: 14px; border: 1px solid var(--border-color); border-radius: 8px; box-sizing: border-box; font-size: 16px; font-family: inherit;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .api-config-container input[type="text"]:focus, .api-config-container input[type="password"]:focus {
            outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 4px var(--focus-glow-color);
        }
        .api-config-container button {
            width: 100%; padding: 15px; background-color: var(--primary-color); color: white; border: none; border-radius: 8px;
            font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s, transform 0.2s ease-out;
        }
        .api-config-container button:hover { background-color: var(--primary-hover-color); transform: translateY(-2px); }
        .api-config-container #message { text-align: center; margin-top: 20px; font-weight: bold; padding: 12px; border-radius: 8px; display: none; }
    </style>
    
    <div class="api-config-container">
       <h2>Configurações da Evolution API</h2>
        <div class="form-group">
            <label for="baseUrl">URL Base da API:</label>
            <input type="text" id="baseUrl" placeholder="Ex: https://api.seusite.com">
        </div>
        <div class="form-group">
            <label for="instanceName">Nome da Instância:</label>
            <input type="text" id="instanceName" placeholder="Ex: minha-instancia">
        </div>
        <div class="form-group">
            <label for="apiKey">API Key:</label>
            <input type="password" id="apiKey" placeholder="Cole sua chave de API aqui">
        </div>
        <button onclick="ev_saveConfig()">Salvar Configurações</button>
        <div id="message"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const config = JSON.parse(localStorage.getItem('evolutionApiConfig'));
            if (config) {
                document.getElementById('baseUrl').value = config.baseUrl || '';
                document.getElementById('instanceName').value = config.instanceName || '';
                document.getElementById('apiKey').value = config.apiKey || '';
            }
        });

        function ev_saveConfig() {
            const config = {
                baseUrl: document.getElementById('baseUrl').value.trim(),
                instanceName: document.getElementById('instanceName').value.trim(),
                apiKey: document.getElementById('apiKey').value.trim()
            };
            const messageDiv = document.querySelector('.api-config-container #message');
            if (!config.baseUrl || !config.instanceName || !config.apiKey) {
                ev_showMessage('🚨 Por favor, preencha todos os campos!', 'error');
                return;
            }
            localStorage.setItem('evolutionApiConfig', JSON.stringify(config));
            ev_showMessage('✅ Configurações salvas com sucesso!', 'success');
        }

        function ev_showMessage(text, type) {
            const messageDiv = document.querySelector('.api-config-container #message');
            messageDiv.textContent = text;
            messageDiv.style.display = 'block';
            if (type === 'success') {
                messageDiv.style.color = '#155724';
                messageDiv.style.backgroundColor = '#d4edda';
            } else {
                messageDiv.style.color = '#721c24';
                messageDiv.style.backgroundColor = '#f8d7da';
            }
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 4000);
        }
    </script>
    <?php
    return ob_get_clean();
}


/**
 * Renderiza o shortcode [evolution_status].
 * Exibe o painel de status da conexão e o QR Code.
 */
function ev_render_status_shortcode() {
    ob_start();
    ?>
    <style>
        .status-viewer-container {
            --primary-color: #007bff; --primary-hover-color: #0056b3; --success-color: #28a745;
            --error-color: #dc3545; --loading-color: #6c757d; --connecting-color: #ffc107;
            --success-bg-color: #e9f7eb; --error-bg-color: #f8d7da; --loading-bg-color: #e2e3e5;
            --connecting-bg-color: #fff3cd; --text-color: #333; --info-text-color: #555; --border-color: #ddd;
            width: 100%; padding: 15px; box-sizing: border-box; text-align: center; font-family: inherit;
        }
        .status-viewer-container h2 { color: var(--primary-color); margin-top: 0; margin-bottom: 25px; font-weight: 700; }
        .status-viewer-container .status-display {
            font-size: 22px; font-weight: bold; padding: 20px; border-radius: 8px; margin-bottom: 20px;
            transition: all 0.3s ease-in-out; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .status-viewer-container .status-connected { color: var(--success-color); background-color: var(--success-bg-color); }
        .status-viewer-container .status-disconnected { color: var(--error-color); background-color: var(--error-bg-color); }
        .status-viewer-container .status-loading { color: var(--loading-color); background-color: var(--loading-bg-color); }
        .status-viewer-container .status-connecting { color: var(--connecting-color); background-color: var(--connecting-bg-color); }
        .status-viewer-container .info { font-size: 16px; color: var(--info-text-color); line-height: 1.6; }
        .status-viewer-container .info strong { color: var(--text-color); }
        .status-viewer-container #error-message { color: var(--error-color); font-weight: 500; margin-top: 15px; text-align: left; padding: 15px; background-color: var(--error-bg-color); border: 1px solid var(--error-color); border-radius: 8px; }
        .status-viewer-container .refresh-button {
            background-color: var(--primary-color); color: white; border: none; border-radius: 8px; padding: 10px 20px;
            font-size: 15px; cursor: pointer; margin-top: 15px; transition: background-color 0.3s, transform 0.2s;
        }
        .status-viewer-container .refresh-button:hover { background-color: var(--primary-hover-color); transform: translateY(-2px); }
        .status-viewer-container #qrcode-container { margin-top: 25px; padding: 20px; border: 1px dashed var(--border-color); border-radius: 8px; display: none; }
        .status-viewer-container #qrcode-container p { margin: 0 0 15px 0; font-weight: 500; color: var(--text-color); }
        .status-viewer-container #qrcode-img { max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto; border: 1px solid var(--border-color); background-color: #fff; }
    </style>

    <div class="status-viewer-container">
        <h2>Status da Conexão</h2>
        <div id="status" class="status-display status-loading">
            <svg id="loader-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
            <span id="status-text">Carregando...</span>
        </div>
        <div class="info">
            <p><strong>Instância Monitorada:</strong> <span id="instance-name">N/A</span></p>
        </div>
        <button id="refresh" class="refresh-button">Verificar Novamente</button>
        <div id="error-message" style="display: none;"></div>
        <div id="qrcode-container">
            <p id="qrcode-text">Escaneie para conectar o WhatsApp</p>
            <img id="qrcode-img" src="" alt="QR Code de Conexão">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Garante que o código só rode se o elemento estiver na página
            const statusContainer = document.querySelector('.status-viewer-container');
            if (!statusContainer) return;

            const refreshButton = statusContainer.querySelector('#refresh');
            
            function runCheck() {
                ev_checkStatus(statusContainer);
            }

            refreshButton.addEventListener('click', runCheck);
            runCheck(); // Primeira verificação
            setInterval(runCheck, 30000); // Verifica a cada 30 segundos
        });

        async function ev_fetchQRCode(config, container) {
            const qrCodeContainer = container.querySelector('#qrcode-container');
            const qrCodeText = container.querySelector('#qrcode-text');
            const qrCodeImg = container.querySelector('#qrcode-img');
            
            qrCodeContainer.style.display = 'block';
            qrCodeText.textContent = 'Carregando QR Code...';
            qrCodeImg.src = '';

            try {
                const url = `${config.baseUrl}/instance/connect/${config.instanceName}`;
                const response = await fetch(url, { method: 'GET', headers: { 'apikey': config.apiKey, 'Accept': 'application/json' } });
                const data = await response.json();
                if (data && data.base64) {
                    qrCodeText.textContent = 'Escaneie para conectar o WhatsApp';
                    qrCodeImg.src = data.base64;
                } else {
                    qrCodeText.textContent = 'Não foi possível carregar o QR Code.';
                }
            } catch (error) {
                qrCodeText.textContent = 'Falha ao carregar o QR Code.';
            }
        }

        async function ev_checkStatus(container) {
            const statusDiv = container.querySelector('#status');
            const instanceNameSpan = container.querySelector('#instance-name');
            const errorDiv = container.querySelector('#error-message');
            const statusText = container.querySelector('#status-text');
            const loaderIcon = container.querySelector('#loader-icon');
            const qrCodeContainer = container.querySelector('#qrcode-container');

            statusDiv.className = 'status-display status-loading';
            statusText.textContent = 'Verificando...';
            loaderIcon.style.display = 'inline-block';
            errorDiv.style.display = 'none';
            qrCodeContainer.style.display = 'none';

            const config = JSON.parse(localStorage.getItem('evolutionApiConfig'));
            if (!config || !config.baseUrl || !config.instanceName || !config.apiKey) {
                statusDiv.className = 'status-display status-disconnected';
                statusText.textContent = 'Configuração Ausente';
                loaderIcon.style.display = 'none';
                instanceNameSpan.textContent = 'Não configurado';
                errorDiv.innerHTML = '🚨 Por favor, vá para a página de <strong>Configuração da API</strong> e salve suas credenciais primeiro.';
                errorDiv.style.display = 'block';
                return;
            }

            instanceNameSpan.textContent = config.instanceName;
            const url = `${config.baseUrl}/instance/connectionState/${config.instanceName}`;
            try {
                const response = await fetch(url, { method: 'GET', headers: { 'apikey': config.apiKey, 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || `Erro HTTP ${response.status}`);
                const currentState = data.instance ? data.instance.state : null;
                loaderIcon.style.display = 'none';
                if (currentState === 'open') {
                    statusDiv.className = 'status-display status-connected';
                    statusText.textContent = 'Conectado';
                } else {
                    if (currentState === 'connecting') {
                        statusDiv.className = 'status-display status-connecting';
                        statusText.textContent = 'Conectando...';
                    } else {
                        statusDiv.className = 'status-display status-disconnected';
                        statusText.textContent = 'Desconectado';
                    }
                    ev_fetchQRCode(config, container);
                }
            } catch (error) {
                statusDiv.className = 'status-display status-disconnected';
                statusText.textContent = 'Erro de Conexão';
                loaderIcon.style.display = 'none';
                errorDiv.innerHTML = `<strong>Falha na comunicação com a API.</strong><br><br><small><i>(Detalhe técnico: ${error.message})</i></small>`;
                errorDiv.style.display = 'block';
            }
        }
    </script>
    <?php
    return ob_get_clean();
}
