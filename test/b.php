<?php
setcookie('Foo1', 111);
setcookie('Foo2', 222);
?>

Lorem ipsum dolor...

<?php
exit;

print('<pre>');
// printf('get -> %s<br>', print_r($_GET, 1));
// printf('post -> %s<br>', print_r($_POST, 1));
// parse_str(file_get_contents('php://input'), $input);
// printf('php://input -> %s<br>', print_r($input, 1));
// print('</pre>');

$fileName = $_POST['fileName'];
$fileData = $_POST['fileData'];

file_put_contents("./$fileName", $fileData);
?>