<?php
// Inicia a sessão com segurança antes de qualquer HTML ser gerado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Help Desk - Sistema de Suporte Técnico Arruda">
    <title>Help Desk - Arruda Empresarial</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Ícone de engrenagem na aba do navegador para todas as páginas -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">
    <link rel="stylesheet" href="styles.css">

    <style>
       /* Variáveis de cores para combinar com a Arruda Empresarial */
        :root {
            --arruda-blue: #2b82b3; /* Azul do site oficial */
            --arruda-light-blue: #3f9acb; /* Azul um pouco mais claro para efeitos */
            --arruda-accent: #5dc171; /* Verde do site oficial */
            --bg-color: #f4f7f6; 
            --text-main: #333333;
            --text-muted: #666666;
        }

        html, body {
            height: 100%;
            margin: 0;
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Banner Central Corporativo */
        .corporate-hero {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background-image: url('img/banner.png'); /* Usa a sua imagem com uma camada branca translúcida por cima */
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            background-color: rgba(255, 255, 255, 0.92); /* Deixa a imagem bem suave no fundo */
        }

        .corporate-card {
            background: #ffffff;
            border-top: 5px solid var(--arruda-blue);
            border-radius: 8px;
            padding: 50px 40px;
            text-align: center;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .corporate-card h1 {
            color: var(--arruda-blue);
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .corporate-card p {
            color: var(--text-muted);
            font-size: 1.15rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        /* Botões Corporativos */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-corporate {
            padding: 14px 30px;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 8px; /* Cantos levemente arredondados, mais sério */
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }

        .btn-employee {
            background-color: var(--arruda-accent);
            color: #ffffff;
            border: 1px solid var(--arruda-accent);
        }

        .btn-employee:hover {
            background-color: var(--arruda-light-blue);
            border-color: var(--arruda-light-blue);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20, 62, 108, 0.2);
        }

        .btn-admin {
            background-color: transparent;
            color: var(--arruda-blue);
            border: 1px solid var(--arruda-blue);
        }

        .btn-admin:hover {
            background-color: var(--bg-color);
            transform: translateY(-2px);
            color: var(--arruda-blue);
        }

        /* Rodapé Corporativo */
        .custom-footer {
            background-color: var(--arruda-blue);
            color: #b0c4d9;
            padding: 30px 20px 20px;
            text-align: center;
            font-size: 0.9rem;
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .custom-footer .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border-radius: 50%;
            margin: 0 6px;
            font-size: 1.1rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .custom-footer .social-links a:hover {
            background: var(--arruda-accent);
            color: #ffffff;
        }

        .custom-footer-links a {
            color: #b0c4d9;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .custom-footer-links a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .corporate-card h1 { font-size: 2.2rem; }
            .corporate-card { padding: 30px 20px; }
            .action-buttons { flex-direction: column; }
            .btn-corporate { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php include 'header.php'; ?>

    <section class="corporate-hero">
        <div class="corporate-card">
            <h1>Suporte Técnico</h1>
            <p>Portal exclusivo de gerenciamento de chamados da <strong>Arruda Empresarial Interno</strong>. Atendimento rápido, organizado e profissional para solucionar seus problemas técnicos.</p>
            
            <div class="action-buttons">
                <a href="index.php" class="btn-corporate btn-employee">
                    <i class="fas fa-headset"></i> Acesso do Funcionário
                </a>
                
                <a href="admin.php" class="btn-corporate btn-admin">
                    <i class="fas fa-shield-alt"></i> Painel Administrativo
                </a>
               <div class="text-center mt-3">
                <a href="https://arrudaempresarial.com.br" class="btn-voltar-site">
                   <i class="fas fa-arrow-left me-2"></i> Voltar para o site principal
                </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="custom-footer">
        <div class="container">
            <div class="social-links mb-4">
                <a href="https://www.facebook.com/arrudaempresarialsp" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/company/arruda-empresarial" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/arrudaempresarial/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://api.whatsapp.com/send?phone=551124535388" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
            
            <p class="mb-3">&copy; 2026 Arruda Empresarial - Help Desk. Todos os direitos reservados.</p>
            
            <div class="custom-footer-links pb-2">
                <a href="https://arrudaempresarial.com.br/politica-de-privacidade-help-desk/">Política de Privacidade</a> | 
                <a href="https://arrudaempresarial.com.br/termos-de-servico-help-desk/">Termos de Serviço</a> | 
            </div>
        </div>
    </footer>
</div>

</script>