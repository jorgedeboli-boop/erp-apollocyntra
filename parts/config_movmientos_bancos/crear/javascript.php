<script>
document.addEventListener('DOMContentLoaded', function () {
  $('.select2').each(function () {
    var $this = $(this);
    $this.select2({ dropdownParent: $this.parent(), width: '100%' });
  });
});
</script>
