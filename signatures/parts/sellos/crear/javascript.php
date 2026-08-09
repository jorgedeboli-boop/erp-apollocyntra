<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('formCrearSello');
  var btnSubmit = document.getElementById('btnCrearSello');
  var btnLoader = document.getElementById('loaderbtn');

  if (!form) {
    return;
  }

  form.addEventListener('submit', function () {
    if (btnSubmit) {
      btnSubmit.style.display = 'none';
    }
    if (btnLoader) {
      btnLoader.style.display = 'inline-flex';
    }
  });

  form.addEventListener('reset', function () {
    setTimeout(function () {
      var radioNo = document.getElementById('sello_logotipo_no');
      if (radioNo) {
        radioNo.checked = true;
      }
      if (btnSubmit) {
        btnSubmit.style.display = '';
      }
      if (btnLoader) {
        btnLoader.style.display = 'none';
      }
    }, 0);
  });
});
</script>
