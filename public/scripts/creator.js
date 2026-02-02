const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const statusText = document.querySelector('#bottom-panel p'); 
const uploadIcon = document.getElementById('upload-icon');
const generateBtn = document.querySelector('.btn-generate');
const creatingPanel = document.getElementById('creating-panel');
const bottomPanel = document.getElementById('bottom-panel');

let uploadedFiles = []; 

function displayMessage(text) {
    let msgElement = document.querySelector('.message');

    if (!msgElement) {
        msgElement = document.createElement('p');
        msgElement.classList.add('message');
        bottomPanel.insertBefore(msgElement, generateBtn);
    }

    msgElement.innerText = text;
}

dropZone.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-btn')) {
        return;
    }
    fileInput.click();
});

fileInput.addEventListener('change', () => {
    handleFiles(fileInput.files);
    fileInput.value = ''; 
});

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});

function handleFiles(files) {
    const newFiles = Array.from(files);

    if (uploadedFiles.length + newFiles.length > 30) {
        displayMessage("You can upload up to 30 photos!");
        return;
    }
    
    displayMessage(""); 

    newFiles.forEach(file => {
        if (!file.type.startsWith('image/')) return;

        uploadedFiles.push(file);

        const reader = new FileReader();
        reader.onload = (e) => {
            const container = document.createElement('div');
            container.classList.add('image-preview-container');

            const img = document.createElement('img');
            img.src = e.target.result;
            img.classList.add('photo-preview');

            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '&times;';
            removeBtn.classList.add('remove-btn');

            removeBtn.onclick = (evt) => {
                evt.stopPropagation(); 
                uploadedFiles = uploadedFiles.filter(f => f !== file);
                container.remove();
                updateStatusText();
                if (uploadedFiles.length === 0 && uploadIcon) {
                    uploadIcon.style.display = 'block';
                }
            };

            container.appendChild(img);
            container.appendChild(removeBtn);

            if (uploadIcon) uploadIcon.style.display = 'none';
            dropZone.appendChild(container);
        };
        reader.readAsDataURL(file);
    });

    updateStatusText();
}

function updateStatusText() {
    if(statusText) {
        statusText.innerText = `Photos uploaded: ${uploadedFiles.length}/30`;
    }
}


generateBtn.addEventListener('click', async () => {
    displayMessage("");

    if (uploadedFiles.length === 0) {
        displayMessage("Upload at least one photo before generating the reel!");
        return;
    }

    const formData = new FormData();
    
    const country = document.getElementById('country-select').value;
    const date = document.getElementById('date-select').value;
    
    formData.append('country', country);
    formData.append('date-select', date);

    uploadedFiles.forEach(file => {
        formData.append('photos[]', file);
    });

    generateBtn.innerText = "Generating...";
    generateBtn.disabled = true;

    try {
        const response = await fetch('/generateReel', {
            method: 'POST',
            body: formData
        });

        const rawResponse = await response.text();
        console.log("Response:", rawResponse);

        let result;
        try {
            result = JSON.parse(rawResponse);
        } catch (e) {
            result = { status: 'error', message: 'Błąd serwera (JSON)' };
        }

        if (response.ok && result.status === 'success') {
            displayMessage("Success! Redirecting...");
            setTimeout(() => {
                window.location.href = '/dashboard'; 
            }, 2000);
        } else {
            displayMessage("Error");
            generateBtn.innerText = "Generate Reel";
            generateBtn.disabled = false;
        }
    } catch (error) {
        displayMessage("Connection error!");
        generateBtn.innerText = "Generate Reel";
        generateBtn.disabled = false;
    }
});