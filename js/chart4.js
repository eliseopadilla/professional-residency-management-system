document.getElementById('selectAll').addEventListener('click', function() {
  const checkboxes = document.querySelectorAll('.student-check');
  checkboxes.forEach(function(checkbox) {
      checkbox.checked = document.getElementById('selectAll').checked;
  });
});
