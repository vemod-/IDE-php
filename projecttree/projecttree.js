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
    if (projects.length == 0) {
	    loadProjects();
    }
});

// 🟢 Hämta projekten från servern
async function loadProjects() {
    return Promise.all([
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
        throw err; // 💥 Viktigt för att .catch i anropet ska triggas
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
	        ae_alert("Could nor save projects","Project Tree");
        }
    });
}
 
let currentFile = '';
let currentProject = '';
let currentPath = '';

function renderTree() {
    const tree = document.getElementById("project-tree");
    tree.innerHTML = "";
    tree.className = "project-tree";
	tree.parentElement.addEventListener("scroll", () => {
	    localStorage.setItem("projectTreeScroll", tree.parentElement.scrollTop);
	});	
	currentFile = tree.dataset.currentFile || "";
    const normalized = currentFile.replace(/^\.\/+/, "");
    const parts = normalized.split("/");
    parts.pop();
    currentPath = parts.length ? "./" + parts.join("/") + "/" : "./";
	const savedProject = localStorage.getItem("currentProject");
	if (savedProject) currentProject = savedProject;
		
    const windowHeader = document.createElement('div');
    windowHeader.innerHTML = '<span>Projects</span>';
    windowHeader.className = 'window-header';

    const addProjctButton = document.createElement('button');
    addProjctButton.innerHTML = textIcon(basePath+'project_icon.png','+');
    addProjctButton.onclick = (e) => {
	    addProject();
	    e.preventDefault();
    }
    windowHeader.appendChild(addProjctButton);
    tree.appendChild(windowHeader);

    const ul = document.createElement("ul");
    ul.className = "rootUl";
    ul.dataset.projectId = '';
    ul.dataset.level = 0;

    const usedAsSubproject = new Set();
    projects.forEach(p => (p.subprojects || []).forEach(title => usedAsSubproject.add(title)));

    projects.forEach((project, projIndex) => {
        //if (usedAsSubproject.has(project.title)) return;
        renderProjectTree(project, projIndex, ul, new Set());
    });

    tree.appendChild(ul);
    const savedScroll = localStorage.getItem("projectTreeScroll");
		if (savedScroll !== null) {
	    tree.parentElement.scrollTop = parseInt(savedScroll, 10);
	}
	markCurrentProject();
}
 
function renderProjectTree(project, projIndex, containerUl, visited, parentProject = '') {
    //if (visited.has(project.title)) return;
    visited.add(project.title);

    const li = document.createElement("li");
    li.onclick = (e) => {
	    setCurrentProject(project.title);
	    markCurrentProject();
	    e.stopPropagation();
	}
    const nested = document.createElement("ul");
    nested.className = "nested";
    nested.dataset.projectId = containerUl.dataset.projectId + '_' + project.title;
	nested.dataset.level = Number(containerUl.dataset.level) + 1;
    
    var subTitle = '';
    if (containerUl.className != 'rootUl') {
	    subTitle = 'sub';
    }
    
    const caret = getCaret(subTitle, basePath + 'project_icon.png', "openProjects", nested.dataset.projectId, nested);
    const projectText = document.createElement("span");
    projectText.className = "folder_text";
    projectText.textContent = project.title;
 
    // ➕ Lägg till fil
    const addBtn = document.createElement("button");
    addBtn.textContent = "+";
    addBtn.title = "Add file";
    addBtn.onclick = (e) => { 
	    addFile(project); 
	    e.preventDefault();
	};
 
    // ➕ Lägg till subprojekt
    const addSubBtn = document.createElement("button");
    //addSubBtn.textContent = "➕";
    addSubBtn.innerHTML = textIcon(basePath+'project_icon.png','+');
    addSubBtn.title = "Add project";
    addSubBtn.onclick = (e) => {
	    addSubProject(project);
	    e.preventDefault();
	};	

    // ❌ Ta bort projekt
    const delBtn = document.createElement("button");
    delBtn.textContent = "-";
    delBtn.title = "Remove project";
    delBtn.onclick = (e) => {
        removeProject(projIndex);
        e.preventDefault();
    };

    // ❌ Ta bort subprojekt
    const delSubBtn = document.createElement("button");
    delSubBtn.textContent = "-";
    delSubBtn.title = "Remove sub project";
    delSubBtn.onclick = (e) => {
        removeSubProject(projIndex,parentProject);
        e.preventDefault();
    };

    const header = document.createElement("div");
    header.dataset.projectTitle = project.title;
    header.className = 'project-div';
    header.appendChild(caret);
    header.appendChild(projectText);
    if (nested.dataset.level == '1') {
	    header.appendChild(delBtn);
    }
    if (nested.dataset.level == '2') {
	    header.appendChild(delSubBtn);
    }
    if (nested.dataset.level == '1') {
	    header.appendChild(addBtn);
	}
    if (nested.dataset.level == '1') {
	    header.appendChild(addSubBtn);
	}

    li.appendChild(header);
    li.appendChild(nested);
    containerUl.appendChild(li);

    const groupedByPostfix = groupByPostfixThenFolder(project);
    Object.entries(groupedByPostfix).forEach(([ext, folders]) => {
        const postfixLi = document.createElement("li");
        const postfixUl = document.createElement("ul");
        postfixUl.className = "nested";

        const caret = getCaret(ext, basePath + 'folder_icon.png', "openPostfix", `${nested.dataset.projectId}/${ext}`, postfixUl);
        const extText = document.createElement("span");
        extText.className = "folder_text";
        extText.textContent = ext || "";
        
        postfixLi.appendChild(caret);
        postfixLi.appendChild(extText);
        postfixLi.appendChild(postfixUl);
        nested.appendChild(postfixLi);

        Object.entries(folders).forEach(([dir, files]) => {
            if (dir === "") {
                addFileItems(postfixUl, project, files, nested.dataset.level);
                return;
            }

            const folderLi = document.createElement("li");
            const folderUl = document.createElement("ul");
            folderUl.className = "nested";

            const folderCaret = getCaret('', basePath + 'folder_icon.png', "openFolders", `${nested.dataset.projectId}/${ext}/${dir}`, folderUl);
            const folderText = document.createElement("span");
            folderText.className = "folder_text";
            folderText.textContent = dir;
            folderText.onclick = (e) => {
            	setCurrentProject(project.title);
			    onFolderClick(dir);
			    e.preventDefault();
	        };

            folderLi.appendChild(folderCaret);
            folderLi.appendChild(folderText);
            folderLi.appendChild(folderUl);
            postfixUl.appendChild(folderLi);

            addFileItems(folderUl, project, files, nested.dataset.level);
        });
    });

    // 🔁 Lägg till subprojects rekursivt
    (project.subprojects || []).forEach(subTitle => {
        const subproject = projects.find(p => p.title === subTitle);
        if (subproject) {
            renderProjectTree(subproject, projects.indexOf(subproject), nested, new Set(visited), project.title);
        }
    });
}

function markCurrentProject() {
    const plist = document.querySelectorAll("div.project-div");
    plist.forEach(function(element) {
        element.style.backgroundColor = 
            (element.dataset.projectTitle == currentProject) ? "#eeeeee" : "white";
    });

}

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

function setCurrentProject(title) {
	currentProject = title;
	localStorage.setItem("currentProject", currentProject);
}

function getCaret(title,icon,storageKey,folderId,folderUl) {
	const caret = document.createElement("span");
    caret.className = "caret";
    caret.innerHTML = textIcon(icon,title,'8') + '&nbsp;';// + title;

	loadFolderState(storageKey,folderId,caret,folderUl);
	
	caret.addEventListener("click", () => {
		folderUl.classList.toggle("active");
	    caret.classList.toggle("caret-down");
	    saveFolderState(storageKey,folderId,folderUl);
	});
	return caret;
}

function addFileItems(folderUl,project,files,level) {
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
	    const fileLi = fileItem(project,file,index,exists,level);
	    folderUl.appendChild(fileLi);
	});
}

function fileItem(project, file, index, exists, level) {
    const fileParts = file.split('/');
    const name = fileParts.length > 2 ? fileParts.pop() : file;
	const isSelected = file === currentFile;
	if (isSelected) {
		if (currentProject == '') {
			setCurrentProject(project.title);
        }
    }
	const liClass = isSelected ? "selected" : "";
	
	const fileLi = document.createElement("li");
	if (liClass) fileLi.classList.add(liClass);
	
	const cssClass = exists ? "file_item" : "file_item missing";
	fileLi.innerHTML = `<span class="${cssClass}">
		<img class='fileimg' src='${basePath}file_icon.png'> ${name}
		</span>`;
	if (exists & !isSelected) {
		fileLi.onclick = (e) => {
			setCurrentProject(project.title);
			onFileClick(file);
		    e.preventDefault();
		};
	}	
	
	const delBtn = document.createElement("button");
	delBtn.textContent = "-";
	delBtn.title = "Remove file";
	delBtn.onclick = (e) => { 
		removeFile(project,index); 
		e.preventDefault();
	};
	if (level == '1') fileLi.appendChild(delBtn);
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
async function addProject() {
    //const title = prompt("Project Name:");
    const title = await ae_prompt_async(`text%¤%Project Name%¤%`, 'Add%¤%1|¤|Cancel%¤%0', 'Add Project');
    if (title != '') {
	    setCurrentProject(title);
	    projects.push({ title, files: [] });
	    saveProjects();
	    renderTree();
    }
}

async function addSubProject(project) {
    if (projects.length < 2) {
	    ae_alert("No projects to choose from","Add Sub Project");
	    return;
    }
	const options = projects
	    .filter(p => p.title !== project.title) // 👈 uteslut det aktuella projektet
	    .map(p => p.title)
		.join(',');
    const value = await ae_prompt_async(`select%¤%Choose project to add%¤%${options}`, 'Add%¤%1|¤|Cancel%¤%0', 'Add Sub Project to ' + project.title);
    if (value == '') {
	    return;
    }
    const selectedTitle = value;
    const existing = projects.find(p => p.title === selectedTitle);
    if (!existing) {
	    ae_alert("Selected project not found","Add Sub Project");
	    return;
    }
    if (selectedTitle && selectedTitle !== project.title) {
        project.subprojects ??= [];
        if (!project.subprojects.includes(selectedTitle)) {
            project.subprojects.push(selectedTitle);
            setCurrentProject(selectedTitle);
            saveProjects();
            renderTree();
        }
    }
}

async function removeProject(projIndex) {
    const removedTitle = projects[projIndex]?.title;
    if (!removedTitle) return;

    if (await ae_confirm_async(`Remove Project ${removedTitle}?`,"Remove Project")) {
        // Ta bort projektet från projects-listan
        projects.splice(projIndex, 1);

        // Gå igenom alla projekt och ta bort det som subprojekt
        projects.forEach(p => {
            if (p.subprojects) {
                p.subprojects = p.subprojects.filter(title => title !== removedTitle);
            }
        });

        saveProjects();
        renderTree();
    }
}

async function removeSubProject(projIndex, parentProject) {
    const project = findProject(parentProject);
    const subprojectTitle = projects[projIndex]['title'];

    if (!subprojectTitle) return;
    if (project.title == subprojectTitle) return;

    if (await ae_confirm_async(`Remove Sub Project ${subprojectTitle} from ${project.title}?`,"Remove Sub Project")) {
        project.subprojects = project.subprojects.filter(title => title !== subprojectTitle);
        setCurrentProject(parentProject);
        saveProjects();
        renderTree();
    }
}
 
function findProject(title) {
    return projects.find(project => project.title === title) || null;
}

function fileAlreadyInProject(file, project) {
    return project.files.some(f => f.path === file);
}

async function addFileToCurrentProject(file) {
	if (file) {
	    var project = findProject(currentProject);
	    if (project) {
		    if (fileAlreadyInProject(file, project)) {
			    ae_alert(file + ' is already in ' + project.title,"Add File");
            }
            else {
			    if (await ae_confirm_async('Add ' + file + ' to ' + project.title,"Add File")) {
			        project.files.push({
					  path: file,
					  exists: true // Eller false om du vill kontrollera det sen
					});
				    saveProjects();
			        renderTree();
		        }
            }
	    }
	}
}

async function addFolderToCurrentProject(files) {
    const project = findProject(currentProject);
    if (!project) {
        ae_alert("No current project found","Add Folder");
        return;
    }

    if (await ae_confirm_async('Add ' + files.length + ' files to ' + project.title,"Add Folder")) {
    
	    let addedCount = 0;
	
	    files.forEach(file => {
	        if (!fileAlreadyInProject(file, project)) {
	            project.files.push({
	                path: file,
	                exists: true // eller kontrollera existens separat om du vill
	            });
	            addedCount++;
	        }
	    });
	
	    if (addedCount > 0) {
	        saveProjects();
	        renderTree();
	    }
	
	    ae_alert(`${addedCount} fil(es) was added to "${project.title}"`,"Add Folder");
	}
}

async function addFile(project) {
    setCurrentProject(project.title);
	//const newFile = prompt("File Path:");
	const newFile = await ae_prompt_async(`text%¤%File Path%¤%./`, 'Add%¤%1|¤|Cancel%¤%0', 'Add File to ' + currentProject);
    if (newFile != '') {
        project.files.push({
		  path: newFile,
		  exists: true // Eller false om du vill kontrollera det sen
		});
	    saveProjects();
        renderTree();
    }
}

async function removeFile(project,index) {
    setCurrentProject(project.title);
    if (await ae_confirm_async("Remove File " + project.files[index].path + " from " + currentProject + "?","Remove File")) {
	    project.files.splice(index, 1);
	    saveProjects();
	    renderTree();
    }
}

function textIcon(iconSource,text,fontSize = '12') {
	return '<div style="position:relative;display:inline;"><img class="fileimg" src="' + iconSource + '"><div style="display:inline;position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);font-size:' + fontSize  + 'px;">' + text.substring(0,3) + '</div></div>';
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
