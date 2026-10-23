{{--
    Session habis (HTTP 419) atau sudah logout (401) saat Livewire mengirim request — mis. lonceng
    notifikasi yang polling setelah tab lama ditinggal. Daripada dialog "This page has expired",
    muat ulang halaman: user "Ingat saya" otomatis masuk lagi, selebihnya diarahkan ke halaman login.
--}}
<script>
    document.addEventListener('livewire:init', () => {
        let reloading = false;

        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status !== 419 && status !== 401) {
                    return;
                }

                preventDefault();

                if (! reloading) {
                    reloading = true;
                    window.location.reload();
                }
            });
        });
    });
</script>
