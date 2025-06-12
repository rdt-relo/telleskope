 <div class="container-offset-lr mt-5">
 <div class="float-start col-12">
 <div class="card mt-2 p-4">
    <div class="row">              
        <div class="col-md-12">
            <div class="widget-simple-chart card-box">
                <div class="col-md-12 p-0" style="padding:0px"><h4>Docs</h4></div>
                <ul class="list-group mt-3 col-md-4">
                    <?php foreach ($files as $file): ?>
                        <?php if (pathinfo($file, PATHINFO_EXTENSION) === 'md'): ?>
                            <li class="list-group-item mb-3 text-center" style="border: 1px solid #d5d3d3; border-radius: 5px;">
                                <a href="view_doc?doc=<?= urlencode($file) ?>" class="px-5 text-primary text-decoration-none">
                                    <?= htmlspecialchars(basename($file, '.md')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                 </ul>
            </div>
        </div>
    </div>
 </div>
 </div>
 </div>
<script>
    $("#sidebar-wrapper ul li:nth-child(14)").addClass("myactive");
</script>
