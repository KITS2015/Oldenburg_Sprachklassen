<?php
declare(strict_types=1);

// optional: $extraJs (string) für seiten-spezifisches JS
?>
        </div> <!-- /.admin-content -->
    </main>
</div> <!-- /.admin-shell -->

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($extraJs)): ?>
    <?php echo $extraJs; ?>
<?php endif; ?>

</body>
</html>
