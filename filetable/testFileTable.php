<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta charset="utf-8">
    <title>FileTable Demo</title>
</head>
<body>
<?php
$ret = "";
$ret .= "<script type=\"text/javascript\">
document.addEventListener(\"DOMContentLoaded\", function () {
    if (typeof FileTableEvents !== 'undefined') {
        FileTableEvents.onFileClick = function (path) {
			alert(path);
            submit_file(path);
        };
        FileTableEvents.onFolderClick = function (path) {
			alert(path);
            submit_dir(path);
        };
		FileTableEvents.onChangeSortOrder = function (sortorder) {
			alert(sortorder);
			submit_sort(sortorder);
		}
        FileTableEvents.onPermissionsClick = function (file,value)
        {
			alert(file + value);
            submit_chmod(file,value);
        }
    }
});
</script>";
require 'filetable.php';
$filetable = new FileTable('.', null, SortOrder::NameAsc, true);
$ret .= $filetable->file_table();
//FileTable::render('.', null, SortOrder::NameAsc, true);
print $ret;
?>
</body>
</html>