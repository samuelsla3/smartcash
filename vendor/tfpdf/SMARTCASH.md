# tFPDF no SmartCash

- Biblioteca: tFPDF 1.34, baseada em FPDF 1.85, de Ian Back e Tycho Veltmeijer.
- Distribuição oficial: https://www.fpdf.org/en/script/script92.php
- ZIP obtido em 19/10/2026: https://www.fpdf.org/en/script/dl.php?id=92&f=zip
- SHA-256 do ZIP original: `400b0dbeadedd5fa18b7b163a6276712d47f8584b3704e2e6f78d09af677cf88`.
- Código da biblioteca preservado, sem alterações. Licença LGPL: veja o cabeçalho de `tfpdf.php`, `info.htm` e `LICENSE.txt`.
- Fontes incluídas: DejaVu Sans Condensed regular, negrito e negrito itálico. Licença em `font/unifont/DejaVu_LICENSE.txt`.
- Métricas de fonte pré-calculadas com caminhos relativos, para uso também no Windows. Não contêm dados dos usuários.

O aplicativo carrega esta biblioteca somente ao exportar PDF. Exige mbstring, já utilizada pelo SmartCash. Não exige Composer, serviço de terceiros, navegador no servidor ou conexão com a internet.
