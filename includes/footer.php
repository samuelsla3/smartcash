        </main>

        <footer class="footer"><span>SmartCash<span class="muted">-</span>IFBA, 2026</span></footer>
    </div>

    <dialog id="confirm-dialog">
        <form method="dialog">
            <div class="dialog-icon"><?= icon('despesas') ?></div>
            <h2>Confirmar ação</h2>
            <p id="confirm-message"></p>
            <div class="actions">
                <button class="btn btn-secondary" value="cancel">Voltar</button>
                <button class="btn btn-danger" value="confirm">Confirmar</button>
            </div>
        </form>
    </dialog>

    <script src="<?= e(assetUrl('assets/js/main.js')) ?>" defer></script>
    <script src="<?= e(assetUrl('assets/js/password-visibility.js')) ?>" defer></script>
    <script src="<?= e(assetUrl('assets/js/usability.js')) ?>" defer></script>
</body>
</html>
