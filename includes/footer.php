<footer class="py-4 mt-5">
    <div class="container text-center">
        <div class="row align-items-center">

            <div class="col-md-4 mb-3 mb-md-0">
                <a href="https://www.google.com/maps/search/?api=1&query=Av.+Almeida+Garret,+930+-+Parque+Taquaral,+Campinas+-+SP"
                    target="_blank" class="text-decoration-none text-light">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    Av. Almeida Garret, 930 - Campinas/SP
                </a>
            </div>

            <div class="col-md-4 mb-3 mb-md-0">
                <a href="https://wa.me/5519992206697" target="_blank" class="text-decoration-none text-light">
                    <i class="bi bi-whatsapp text-success me-2"></i>
                    (19) 99220-6690
                </a>
            </div>

            <div class="col-md-4">
                <a href="https://www.instagram.com/big_head_tattoo" target="_blank" class="text-decoration-none text-light">
                    <i class="bi bi-instagram me-2" style="color: #E1306C;"></i>
                    @big_head_tattoo
                </a>
            </div>

        </div>
        <p class="mt-4 mb-0 small text-white-50">© 2026 | Big Head Tattoo</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {

                if (this.checkValidity()) {


                    const submitButtons = this.querySelectorAll('button[type="submit"], input[type="submit"]');

                    submitButtons.forEach(btn => {

                        if (!btn.disabled) {

                            btn.disabled = true;

                            if (btn.tagName.toLowerCase() === 'button') {

                                const width = btn.offsetWidth;
                                btn.style.width = width + 'px';

                                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';
                            } else {

                                btn.value = 'Aguarde...';
                            }
                        }
                    });
                }
            });
        });
    });
</script>

</body>

</html>