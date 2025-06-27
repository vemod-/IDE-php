let projects = [];
let modeMap = {};

    const basePath = document.currentScript?.src
    ? new URL('.', document.currentScript.src).pathname
    : '/idephp/projecttree/'; // fallback

window.ProjectTreeEvents = {
    onFileClick: (path) => console.log("Default file click:", path),
    onFolderClick: (path) => console.log("Default folder click:", path),
}; 

document.addEventListener("DOMContentLoaded", () => {
  loadProjects();
});

// 🟢 Hämta projekten från servern
function loadProjects() {
    Promise.all([
        fetch(basePath + 'projects.php').then(res => res.json()),
        fetch(basePath + '../code_modes.json').then(res => res.json())
    ])
    .then(([projectsData, modeMapData]) => {
        projects = projectsData;
        modeMap = modeMapData;
        renderTree(); // Kör när båda är klara
    })
    .catch(err => {
        console.error("Fel vid laddning av data:", err);
    });
}
// 💾 Spara projekt till servern
function saveProjects() {
    fetch(basePath + 'projects.php', {
	    method: 'POST',
	    headers: { 'Content-Type': 'application/json' },
	    body: JSON.stringify(projects)
    }).then(res => res.json())
    .then(response => {
        if (response.status !== 'success') {
	        alert("Fel vid sparande av projekt.");
        }
    });
}

// 🔄 Rendera trädet
function renderTree() {
    const tree = document.getElementById("project-tree");
    tree.innerHTML = "";
    tree.className = "project-tree";
    
	const currentFile = tree.dataset.currentFile || "";
	const normalized = currentFile.replace(/^\.\/+/, ""); // tar bort inledande ./
	const parts = normalized.split("/");
	parts.pop(); // ta bort själva filnamnet
	const currentPath = parts.length ? "./" + parts.join("/") + "/" : "./";
	    
	const windowHeader = document.createElement('div');
	windowHeader.innerHTML = '<span>Projects</span>';
	windowHeader.className = 'window-header';    
    const addProjctButton = document.createElement('button');
    addProjctButton.innerHTML = '<div style="position:relative;"><img class="fileimg" src="'+basePath+'project_icon.png"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);">+</div></div>';
    addProjctButton.onclick = addProject;
    windowHeader.appendChild(addProjctButton);
    tree.appendChild(windowHeader);
        
    const ul = document.createElement("ul");
    ul.className  = "rootUl";
	
	projects.forEach((project, projIndex) => {
	    const li = document.createElement("li");
	
		const nested = document.createElement("ul");
		nested.className = "nested";

		const caret = getCaret(project.title,basePath + 'project_icon.png',"openProjects",project.title,nested);
		// ➕ Lägg till fil
	    const addBtn = document.createElement("button");
	    addBtn.textContent = "+";
	    addBtn.onclick = () => { addFile(project,currentPath); };
		
	    // ❌ Ta bort projekt
	    const delBtn = document.createElement("button");
	    delBtn.textContent = "-";
	    delBtn.onclick = () => {
	        removeProject(projIndex);
	    };
	
	    const header = document.createElement("div");
	    header.appendChild(caret);
	    header.appendChild(delBtn);
	    header.appendChild(addBtn);
	
	    li.appendChild(header);
	    li.appendChild(nested);
	    ul.appendChild(li);
		
		const groupedByPostfix = groupByPostfixThenFolder(project);
		
		Object.entries(groupedByPostfix).forEach(([ext, folders]) => {
			const postfixLi = document.createElement("li");
			const postfixUl = document.createElement("ul");
			postfixUl.className = "nested";
		
			const caret = getCaret(ext || "", basePath + 'folder_icon.png', "openPostfix", `${project.title}/${ext}`, postfixUl);
			postfixLi.appendChild(caret);
			postfixLi.appendChild(postfixUl);
			nested.appendChild(postfixLi);
		
			Object.entries(folders).forEach(([dir, files]) => {
				if (dir === "") {
					addFileItems(postfixUl, project, files, currentFile);
					return;
				}
		
				const folderLi = document.createElement("li");
				const folderUl = document.createElement("ul");
				folderUl.className = "nested";
		
				const folderCaret = getCaret('', basePath + 'folder_icon.png', "openFolders", `${project.title}/${ext}/${dir}`, folderUl);
				const folderText = document.createElement("span");
				folderText.className = "folder_text";
				folderText.textContent = dir;
				folderText.onclick = () => onFolderClick(dir);
		
				folderLi.appendChild(folderCaret);
				folderLi.appendChild(folderText);
				folderLi.appendChild(folderUl);
				postfixUl.appendChild(folderLi);
		
				addFileItems(folderUl, project, files, currentFile);
			});
		});
		
	});

    tree.appendChild(ul);
}
/*
function groupByPostfixThenFolder(project) {
	const grouped = {};

	project.files.forEach((fileObj, i) => {
		const file = fileObj.path;
		const exists = fileObj.exists;
		const cleanPath = file.replace(/^\.\/+/, "");
		
		const postfixParts = cleanPath.split('.');
		let ext = postfixParts.length > 1 ? postfixParts.pop().toLowerCase() : '';
		let mode = modeMap[ext] || 'other files'; // använd modeMap här
		const withoutExt = postfixParts.join('.');
		
		const pathParts = withoutExt.split('/');
		const folder = pathParts.length > 1 ? './' + pathParts.slice(0, -1).join('/') : '';

		const item = { file, index: i, exists };

		(grouped[mode] ??= {});
		(grouped[mode][folder] ??= []).push(item);
	});

	return grouped; // t.ex. { php: { "./": [...], "./folder": [...] }, js: { "./scripts": [...] } }
}
*/
function groupByPostfixThenFolder(project) {
	const temp = {};

	project.files.forEach((fileObj, i) => {
		const file = fileObj.path;
		const exists = fileObj.exists;
		const cleanPath = file.replace(/^\.\/+/, "");

		const postfixParts = cleanPath.split('.');
		let ext = postfixParts.length > 1 ? postfixParts.pop().toLowerCase() : '';
		let mode = modeMap[ext] || 'other files';
		const withoutExt = postfixParts.join('.');

		const pathParts = withoutExt.split('/');
		const folder = pathParts.length > 1 ? './' + pathParts.slice(0, -1).join('/') : '';

		const item = { file, index: i, exists };

		(temp[mode] ??= {});
		(temp[mode][folder] ??= []).push(item);
	});

	// Sortera ytternivån (mode) och innervåningen (folder)
	const sortedGrouped = {};
	for (const mode of Object.keys(temp).sort((a, b) => a.localeCompare(b))) {
		sortedGrouped[mode] = {};
		for (const folder of Object.keys(temp[mode]).sort((a, b) => a.localeCompare(b))) {
			sortedGrouped[mode][folder] = temp[mode][folder];
		}
	}

	return sortedGrouped;
}

function getCaret(title,icon,storageKey,folderId,folderUl) {
	const caret = document.createElement("span");
    caret.className = "caret";
    caret.innerHTML = `<img class="fileimg" src="${icon}"> ${title} `;

	loadFolderState(storageKey,folderId,caret,folderUl);
	
	caret.addEventListener("click", () => {
		folderUl.classList.toggle("active");
	    caret.classList.toggle("caret-down");
	    saveFolderState(storageKey,folderId,folderUl);
	});
	return caret;
}

function addFileItems(folderUl,project,files,currentFile) {
    /*
    const sortedFiles = files.slice().sort((a, b) => {
	    const extA = a.file.split('.').pop().toLowerCase();
	    const extB = b.file.split('.').pop().toLowerCase();
	    return extA.localeCompare(extB);
	});
	*/
	const sortedFiles = files.slice().sort((a, b) => {
	    const nameA = a.file.split('/').pop().toLowerCase();
	    const nameB = b.file.split('/').pop().toLowerCase();
	    return nameA.localeCompare(nameB);
	});
	sortedFiles.forEach(({ file, index, exists }) => {
	    const fileLi = fileItem(project,file,index,exists,currentFile);
	    folderUl.appendChild(fileLi);
	});
}

function fileItem(project, file, index, exists, currentFile) {
    const fileParts = file.split('/');
    const name = fileParts.length > 2 ? fileParts.pop() : file;
	const isSelected = file === currentFile;
	const liClass = isSelected ? "selected" : "";
	
	const fileLi = document.createElement("li");
	if (liClass) fileLi.classList.add(liClass);
	
	const cssClass = exists ? "file_item" : "file_item missing";
	fileLi.innerHTML = `<span class="${cssClass}">
		<img class='fileimg' src='${basePath}file_icon.png'> ${name}
		</span>`;
	if (exists & !isSelected) {
		fileLi.onclick = () => {
			onFileClick(file);
		};
	}	
	
	const delBtn = document.createElement("button");
	delBtn.textContent = "-";
	delBtn.onclick = () => { removeFile(project,index); };
	fileLi.appendChild(delBtn);
	return fileLi;
}

function loadFolderState(storageKey,folderId,caret,folderUl) {
    let openFolders = JSON.parse(localStorage.getItem(storageKey) || "[]");
	if (openFolders.includes(folderId)) {
        caret.classList.add("caret-down");
        folderUl.classList.add("active");
    }
}

function saveFolderState(storageKey,folderId,folderUl) {
    let openFolders = JSON.parse(localStorage.getItem(storageKey) || "[]");
	const index = openFolders.indexOf(folderId);
    if (folderUl.classList.contains("active")) {
        if (index === -1) openFolders.push(folderId);
    } else {
        if (index !== -1) openFolders.splice(index, 1);
    }
    localStorage.setItem(storageKey, JSON.stringify(openFolders));
}

// ➕ Lägg till projekt
function addProject() {
    const title = prompt("Project Name:");
    if (title) {
	    projects.push({ title, files: [] });
	    saveProjects();
	    renderTree();
    }
}

function removeProject(projIndex) {
    if (confirm("Remove Project \"" + projects[projIndex]['title'] + "\"?")) {
        projects.splice(projIndex, 1);
        saveProjects();
        renderTree();
    }
}

function addFile(project,currentPath) {
	const newFile = prompt("File Path:",currentPath);
    if (newFile) {
        project.files.push({
		  path: newFile,
		  exists: true // Eller false om du vill kontrollera det sen
		});
	    saveProjects();
        renderTree();
    }
}

function removeFile(project,index) {
    if (confirm("Remove File \"" + project.files[index].path + "\"?")) {
	    project.files.splice(index, 1);
	    saveProjects();
	    renderTree();
    }
}

// 🔘 Hantera filklick
function onFileClick(path) {
    if (typeof ProjectTreeEvents?.onFileClick === "function") {
	    ProjectTreeEvents.onFileClick(path);
    } else {
	    console.log("File clicked:", path);
    }
}

// 🔘 Hantera filklick
function onFolderClick(path) {
    if (typeof ProjectTreeEvents?.onFolderClick === "function") {
	    ProjectTreeEvents.onFolderClick(path);
    } else {
	    console.log("Folder clicked:", path);
    }
}
