let hide_pass = document.getElementById('eyeicon');
let password = document.getElementById('password');

hide_pass.onclick = function() {
  if (password.type === 'password') {
    password.type = 'text';
    hide_pass.src = 'img/show_pass.png';
  } else {
    password.type = 'password';
    hide_pass.src = 'img/hide_pass.png';
  }
}