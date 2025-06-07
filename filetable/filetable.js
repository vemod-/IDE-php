window.FileTableEvents = {
    onFileClick: (path) => console.log("Default file click:", path),
    onFolderClick: (name) => console.log("Default folder click:", name),
    onPermissionsClick: (path,value) => console.log("Default permissions click:", path),
    onChangeSortOrder: (sortorder) => console.log("Default header click:", sortorder)
};

function onChangeSortOrder(sortorder)
{
	FileTableEvents.onChangeSortOrder(sortorder);
}

function onFolderClick(path)
{
    FileTableEvents.onFolderClick(path);
}

function onFileClick(path)
{
    FileTableEvents.onFileClick(path);
}

function onPermissionsClick(file,value)
{
    FileTableEvents.onPermissionsClick(file,value);
}