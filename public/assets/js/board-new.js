// Classe para gerenciar o board
class BoardManager {
    constructor() {
        this.apiBase = '/';
        this.boardId = this.getBoardIdFromUrl();
        this.currentBoard = null;
        this.lists = [];
        this.allTags = []; // Cache de todas as tags
        this.currentCardId = null; // Card sendo editado no modal de tags
        this.init();
    }

    // Pega o ID do board da URL
    getBoardIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    // Inicializa a aplicação
    init() {
        if (!this.boardId) {
            this.showError('ID do board não encontrado');
            setTimeout(() => {
                window.location.href = '/';
            }, 2000);
            return;
        }

        this.setupEventListeners();
        this.loadBoard();
        this.loadAllTags(); // Carrega todas as tags disponíveis
    }

    // Configura os event listeners
    setupEventListeners() {
        // Botão voltar
        document.getElementById('backToHome').addEventListener('click', () => {
            window.location.href = '/';
        });

        // Botão adicionar lista
        document.getElementById('addListBtn').addEventListener('click', () => {
            this.openListModal();
        });

        // Modais
        this.setupModalListeners();
    }

    // Configura listeners dos modais
    setupModalListeners() {
        // Modal de lista
        const listModal = document.getElementById('listModal');
        const listForm = document.getElementById('listForm');
        const cancelListBtn = document.getElementById('cancelListBtn');

        listForm.addEventListener('submit', (e) => this.handleListSubmit(e));
        cancelListBtn.addEventListener('click', () => this.closeModal('listModal'));

        // Modal de card
        const cardModal = document.getElementById('cardModal');
        const cardForm = document.getElementById('cardForm');
        const cancelCardBtn = document.getElementById('cancelCardBtn');

        cardForm.addEventListener('submit', (e) => this.handleCardSubmit(e));
        cancelCardBtn.addEventListener('click', () => this.closeModal('cardModal'));

        // Modal de visualização de card
        const viewCardModal = document.getElementById('viewCardModal');
        const closeViewCard = document.getElementById('closeViewCard');
        const editCardFromView = document.getElementById('editCardFromView');
        const deleteCardFromView = document.getElementById('deleteCardFromView');
        const manageTagsBtn = document.getElementById('manageTagsBtn');

        closeViewCard.addEventListener('click', () => this.closeModal('viewCardModal'));
        editCardFromView.addEventListener('click', () => this.editCardFromView());
        deleteCardFromView.addEventListener('click', () => this.deleteCardFromView());
        manageTagsBtn.addEventListener('click', () => this.openTagsModal());

        // Modal de tags
        const tagsModal = document.getElementById('tagsModal');
        const closeTagsModal = document.getElementById('closeTagsModal');
        const cancelTagsBtn = document.getElementById('cancelTagsBtn');
        const saveTagsBtn = document.getElementById('saveTagsBtn');
        const createNewTagBtn = document.getElementById('createNewTagBtn');
        const cancelNewTag = document.getElementById('cancelNewTag');
        const newTagForm = document.getElementById('newTagForm');

        closeTagsModal.addEventListener('click', () => this.closeModal('tagsModal'));
        cancelTagsBtn.addEventListener('click', () => this.closeModal('tagsModal'));
        saveTagsBtn.addEventListener('click', () => this.saveCardTags());
        createNewTagBtn.addEventListener('click', () => this.showNewTagForm());
        cancelNewTag.addEventListener('click', () => this.hideNewTagForm());
        newTagForm.addEventListener('submit', (e) => this.handleNewTagSubmit(e));

        // Modal de confirmação
        const deleteModal = document.getElementById('deleteModal');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');

        confirmDelete.addEventListener('click', () => this.confirmDelete());
        cancelDelete.addEventListener('click', () => this.closeModal('deleteModal'));

        // Fechar modais ao clicar fora
        [listModal, cardModal, viewCardModal, deleteModal, tagsModal].forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal.id);
                }
            });
        });
    }

    // Carrega dados do board
    async loadBoard() {
        try {
            // Carrega informações básicas do board
            const boardResponse = await fetch(`${this.apiBase}boards`);
            if (!boardResponse.ok) throw new Error('Erro ao carregar boards');
            
            const boards = await boardResponse.json();
            this.currentBoard = boards.find(b => b.id == this.boardId);
            
            if (!this.currentBoard) {
                throw new Error('Board não encontrado');
            }

            document.getElementById('boardTitle').textContent = this.currentBoard.title;
            
            // Carrega as listas
            await this.loadLists();
            
        } catch (error) {
            console.error('Erro ao carregar board:', error);
            this.showError('Erro ao carregar o board');
        }
    }

    // Carrega todas as tags
    async loadAllTags() {
        try {
            const response = await fetch(`${this.apiBase}tags`);
            if (!response.ok) throw new Error('Erro ao carregar tags');
            this.allTags = await response.json();
        } catch (error) {
            console.error('Erro ao carregar tags:', error);
            this.allTags = [];
        }
    }

    // Carrega as listas do board
    async loadLists() {
        try {
            const response = await fetch(`${this.apiBase}boards/${this.boardId}/lists`);
            if (!response.ok) throw new Error('Erro ao carregar listas');
            
            this.lists = await response.json();
            await this.renderLists();
            
        } catch (error) {
            console.error('Erro ao carregar listas:', error);
            this.showError('Erro ao carregar as listas');
        }
    }

    // Renderiza as listas
    async renderLists() {
        const container = document.getElementById('listsContainer');
        
        if (this.lists.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>Nenhuma lista encontrada</h3>
                    <p>Crie sua primeira lista para começar a organizar suas tarefas</p>
                    <button onclick="boardManager.openListModal()" class="btn-primary">+ Criar Lista</button>
                </div>
            `;
            return;
        }

        const listsHTML = await Promise.all(
            this.lists.map(async list => await this.renderList(list))
        );
        
        container.innerHTML = listsHTML.join('');
        this.setupDragAndDrop();
    }

    // Renderiza uma lista
    async renderList(list) {
        const cards = await this.loadCards(list.id);
        const cardsHTML = cards.map(card => this.renderCard(card)).join('');

        return `
            <div class="list" data-list-id="${list.id}">
                <div class="list-header">
                    <h3 class="list-title">${this.escapeHtml(list.title)}</h3>
                    <div class="list-actions">
                        <button onclick="boardManager.editList(${list.id})" title="Editar lista">✏️</button>
                        <button onclick="boardManager.deleteList(${list.id})" title="Excluir lista">🗑️</button>
                    </div>
                </div>
                <div class="cards-container" data-list-id="${list.id}">
                    ${cardsHTML}
                </div>
                <div class="list-footer">
                    <button onclick="boardManager.openCardModal(${list.id})" class="add-card-btn">+ Adicionar card</button>
                </div>
            </div>
        `;
    }

    // Carrega cards de uma lista
    async loadCards(listId) {
        try {
            const response = await fetch(`${this.apiBase}lists/${listId}/cards`);
            if (!response.ok) throw new Error('Erro ao carregar cards');
            return await response.json();
        } catch (error) {
            console.error('Erro ao carregar cards:', error);
            return [];
        }
    }

    // Renderiza um card
    renderCard(card) {
        const description = card.description ? 
            `<div class="card-description">${this.escapeHtml(card.description)}</div>` : '';

        // Renderiza as tags
        const tagsHTML = this.renderCardTags(card.tags || []);

        return `
            <div class="card" 
                 data-card-id="${card.id}" 
                 data-list-id="${card.list_id}"
                 draggable="true"
                 onclick="boardManager.handleCardClick(${card.id}, event)">
                ${tagsHTML}
                <div class="card-title">${this.escapeHtml(card.title)}</div>
                ${description}
                <div class="card-meta">
                    <span>${this.formatDate(card.created_at)}</span>
                    <div class="card-actions">
                        <button onclick="event.stopPropagation(); boardManager.editCard(${card.id})" title="Editar card">✏️</button>
                        <button onclick="event.stopPropagation(); boardManager.deleteCard(${card.id})" title="Excluir card">🗑️</button>
                    </div>
                </div>
            </div>
        `;
    }

    // Renderiza as tags do card
    renderCardTags(tags) {
        if (!tags || tags.length === 0) {
            return '';
        }

        const tagsHTML = tags.map(tag => 
            `<span class="tag" style="background-color: ${tag.color}" title="${this.escapeHtml(tag.name)}">
                ${this.escapeHtml(tag.name)}
            </span>`
        ).join('');

        return `<div class="card-tags">${tagsHTML}</div>`;
    }

    // Abre modal de tags
    async openTagsModal() {
        if (!this.currentCardId) return;

        try {
            // Carrega dados atuais do card com tags
            const response = await fetch(`${this.apiBase}cards/${this.currentCardId}`);
            if (!response.ok) throw new Error('Erro ao carregar card');
            
            const card = await response.json();
            
            // Atualiza cache de tags
            await this.loadAllTags();
            
            // Renderiza o modal
            this.renderTagsModal(card);
            this.openModal('tagsModal');
            
        } catch (error) {
            console.error('Erro ao abrir modal de tags:', error);
            this.showError('Erro ao carregar tags do card');
        }
    }

    // Renderiza o modal de tags
    renderTagsModal(card) {
        const cardTags = card.tags || [];
        const cardTagIds = cardTags.map(tag => tag.id);

        // Renderiza tags atuais do card
        const currentTagsHTML = cardTags.length > 0 
            ? cardTags.map(tag => `
                <span class="tag removable" style="background-color: ${tag.color}" data-tag-id="${tag.id}">
                    ${this.escapeHtml(tag.name)}
                    <button class="tag-remove" onclick="boardManager.removeTagFromCard(${tag.id})">×</button>
                </span>
            `).join('')
            : '<div class="no-tags">Nenhuma tag adicionada</div>';

        document.getElementById('cardCurrentTags').innerHTML = currentTagsHTML;

        // Renderiza tags disponíveis
        const availableTagsHTML = this.allTags.map(tag => {
            const isInCard = cardTagIds.includes(tag.id);
            const cssClass = isInCard ? 'tag-item in-card' : 'tag-item';
            const actionBtn = isInCard 
                ? `<button class="tag-action-btn" onclick="boardManager.removeTagFromCard(${tag.id})" title="Remover">−</button>`
                : `<button class="tag-action-btn" onclick="boardManager.addTagToCard(${tag.id})" title="Adicionar">+</button>`;

            return `
                <div class="${cssClass}" data-tag-id="${tag.id}">
                    <div class="tag-info">
                        <div class="tag-preview-large" style="background-color: ${tag.color}">
                            ${this.escapeHtml(tag.name)}
                        </div>
                        <div class="tag-meta">${tag.name}</div>
                    </div>
                    <div class="tag-actions">
                        ${actionBtn}
                    </div>
                </div>
            `;
        }).join('');

        document.getElementById('availableTags').innerHTML = availableTagsHTML || '<div class="no-tags">Nenhuma tag disponível</div>';
    }

    // Adiciona tag ao card (temporário, salva depois)
    addTagToCard(tagId) {
        const tagItem = document.querySelector(`[data-tag-id="${tagId}"]`);
        const actionBtn = tagItem.querySelector('.tag-action-btn');
        
        tagItem.classList.remove('tag-item');
        tagItem.classList.add('tag-item', 'in-card');
        actionBtn.innerHTML = '−';
        actionBtn.onclick = () => this.removeTagFromCard(tagId);
        actionBtn.title = 'Remover';

        // Adiciona na lista de tags atuais
        const tag = this.allTags.find(t => t.id === tagId);
        if (tag) {
            const currentTagsContainer = document.getElementById('cardCurrentTags');
            const noTagsDiv = currentTagsContainer.querySelector('.no-tags');
            if (noTagsDiv) {
                noTagsDiv.remove();
            }

            const tagElement = document.createElement('span');
            tagElement.className = 'tag removable';
            tagElement.style.backgroundColor = tag.color;
            tagElement.setAttribute('data-tag-id', tag.id);
            tagElement.innerHTML = `
                ${this.escapeHtml(tag.name)}
                <button class="tag-remove" onclick="boardManager.removeTagFromCard(${tag.id})">×</button>
            `;
            currentTagsContainer.appendChild(tagElement);
        }
    }

    // Remove tag do card (temporário, salva depois)
    removeTagFromCard(tagId) {
        const tagItem = document.querySelector(`#availableTags [data-tag-id="${tagId}"]`);
        const tagInCurrent = document.querySelector(`#cardCurrentTags [data-tag-id="${tagId}"]`);
        
        if (tagItem) {
            const actionBtn = tagItem.querySelector('.tag-action-btn');
            tagItem.classList.remove('in-card');
            actionBtn.innerHTML = '+';
            actionBtn.onclick = () => this.addTagToCard(tagId);
            actionBtn.title = 'Adicionar';
        }

        if (tagInCurrent) {
            tagInCurrent.remove();
        }

        // Verifica se não há mais tags e adiciona mensagem
        const currentTagsContainer = document.getElementById('cardCurrentTags');
        if (currentTagsContainer.children.length === 0) {
            currentTagsContainer.innerHTML = '<div class="no-tags">Nenhuma tag adicionada</div>';
        }
    }

    // Salva as alterações de tags do card
    async saveCardTags() {
        try {
            const currentTags = document.querySelectorAll('#cardCurrentTags .tag[data-tag-id]');
            const tagIds = Array.from(currentTags).map(tag => parseInt(tag.getAttribute('data-tag-id')));

            const response = await fetch(`${this.apiBase}cards/${this.currentCardId}/tags`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tags: tagIds })
            });

            if (!response.ok) throw new Error('Erro ao salvar tags');

            this.closeModal('tagsModal');
            this.showSuccess('Tags atualizadas com sucesso!');
            
            // Atualiza a visualização
            await this.loadLists();
            
            // Se o modal de visualização estiver aberto, atualiza também
            if (document.getElementById('viewCardModal').style.display !== 'none') {
                await this.loadCardForView(this.currentCardId);
            }

        } catch (error) {
            console.error('Erro ao salvar tags:', error);
            this.showError('Erro ao salvar tags do card');
        }
    }

    // Mostra formulário de nova tag
    showNewTagForm() {
        document.getElementById('newTagSection').classList.remove('hidden');
        document.getElementById('newTagName').focus();
    }

    // Esconde formulário de nova tag
    hideNewTagForm() {
        document.getElementById('newTagSection').classList.add('hidden');
        document.getElementById('newTagForm').reset();
    }

    // Manipula submissão de nova tag
    async handleNewTagSubmit(e) {
        e.preventDefault();
        
        const name = document.getElementById('newTagName').value.trim();
        const color = document.getElementById('newTagColor').value;

        if (!name) return;

        try {
            const response = await fetch(`${this.apiBase}tags`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, color })
            });

            if (!response.ok) throw new Error('Erro ao criar tag');

            const newTag = await response.json();
            this.allTags.push(newTag);
            
            this.hideNewTagForm();
            this.showSuccess('Tag criada com sucesso!');
            
            // Atualiza a visualização das tags disponíveis
            const card = { tags: this.getCurrentCardTags() };
            this.renderTagsModal(card);

        } catch (error) {
            console.error('Erro ao criar tag:', error);
            this.showError('Erro ao criar nova tag');
        }
    }

    // Pega as tags atuais do card no modal
    getCurrentCardTags() {
        const currentTags = document.querySelectorAll('#cardCurrentTags .tag[data-tag-id]');
        return Array.from(currentTags).map(tagElement => {
            const tagId = parseInt(tagElement.getAttribute('data-tag-id'));
            return this.allTags.find(tag => tag.id === tagId);
        }).filter(tag => tag);
    }

    // Clique no card - abre modal de visualização
    async handleCardClick(cardId, event) {
        if (event.target.closest('.card-actions')) {
            return; // Não abre modal se clicou em ação
        }

        this.currentCardId = cardId;
        await this.loadCardForView(cardId);
        this.openModal('viewCardModal');
    }

    // Carrega card para visualização
    async loadCardForView(cardId) {
        try {
            const response = await fetch(`${this.apiBase}cards/${cardId}`);
            if (!response.ok) throw new Error('Erro ao carregar card');
            
            const card = await response.json();
            const list = this.lists.find(l => l.id == card.list_id);
            
            document.getElementById('viewCardTitle').textContent = card.title;
            document.getElementById('viewCardDescription').innerHTML = card.description 
                ? this.escapeHtml(card.description).replace(/\n/g, '<br>')
                : '<em>Sem descrição</em>';
            document.getElementById('viewCardList').textContent = list ? list.title : 'Lista não encontrada';
            document.getElementById('viewCardDate').textContent = this.formatDate(card.created_at);
            
            // Renderiza tags na visualização
            this.renderCardTagsInView(card.tags || []);
            
        } catch (error) {
            console.error('Erro ao carregar card:', error);
            this.showError('Erro ao carregar detalhes do card');
        }
    }

    // Renderiza tags na visualização do card
    renderCardTagsInView(tags) {
        const container = document.getElementById('viewCardTags');
        
        if (tags.length === 0) {
            container.innerHTML = '<div class="no-tags">Nenhuma tag adicionada</div>';
            return;
        }

        const tagsHTML = tags.map(tag => 
            `<span class="tag" style="background-color: ${tag.color}" title="${this.escapeHtml(tag.name)}">
                ${this.escapeHtml(tag.name)}
            </span>`
        ).join('');

        container.innerHTML = tagsHTML;
    }

    // Continue com os métodos existentes...
    // [Resto do código permanece igual - métodos de CRUD, drag&drop, etc.]

    // Configura drag and drop
    setupDragAndDrop() {
        // Remove listeners anteriores se existirem
        this.removeDragListeners();

        // Adiciona listeners para todos os cards
        document.querySelectorAll('.card[draggable="true"]').forEach(card => {
            card.addEventListener('dragstart', this.handleDragStart.bind(this));
            card.addEventListener('dragend', this.handleDragEnd.bind(this));
        });

        // Adiciona listeners para todas as áreas de drop
        document.querySelectorAll('.cards-container').forEach(container => {
            container.addEventListener('dragover', this.handleDragOver.bind(this));
            container.addEventListener('drop', this.handleDrop.bind(this));
            container.addEventListener('dragenter', this.handleDragEnter.bind(this));
            container.addEventListener('dragleave', this.handleDragLeave.bind(this));
        });
    }

    // Remove listeners de drag para evitar duplicação
    removeDragListeners() {
        document.querySelectorAll('.card[draggable="true"]').forEach(card => {
            card.removeEventListener('dragstart', this.handleDragStart);
            card.removeEventListener('dragend', this.handleDragEnd);
        });

        document.querySelectorAll('.cards-container').forEach(container => {
            container.removeEventListener('dragover', this.handleDragOver);
            container.removeEventListener('drop', this.handleDrop);
            container.removeEventListener('dragenter', this.handleDragEnter);
            container.removeEventListener('dragleave', this.handleDragLeave);
        });
    }

    // Métodos de drag and drop
    handleDragStart(e) {
        const card = e.target;
        card.classList.add('dragging');
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.cardId);
        
        // Adiciona classe visual para áreas de drop
        document.querySelectorAll('.cards-container').forEach(container => {
            container.classList.add('drop-zone-active');
        });
    }

    handleDragEnd(e) {
        const card = e.target;
        card.classList.remove('dragging');
        
        // Remove classes visuais
        document.querySelectorAll('.cards-container').forEach(container => {
            container.classList.remove('drop-zone-active', 'drop-zone-hover');
        });
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    handleDragEnter(e) {
        e.preventDefault();
        const container = e.currentTarget;
        container.classList.add('drop-zone-hover');
    }

    handleDragLeave(e) {
        const container = e.currentTarget;
        if (!container.contains(e.relatedTarget)) {
            container.classList.remove('drop-zone-hover');
        }
    }

    async handleDrop(e) {
        e.preventDefault();
        
        const container = e.currentTarget;
        container.classList.remove('drop-zone-hover');
        
        const cardId = e.dataTransfer.getData('text/plain');
        const newListId = container.dataset.listId;
        
        if (!cardId || !newListId) return;
        
        try {
            const response = await fetch(`${this.apiBase}cards/${cardId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ list_id: newListId })
            });

            if (!response.ok) throw new Error('Erro ao mover card');
            
            await this.loadLists(); // Recarrega as listas
            this.showSuccess('Card movido com sucesso!');
            
        } catch (error) {
            console.error('Erro ao mover card:', error);
            this.showError('Erro ao mover o card');
        }
    }

    // CRUD de Listas
    openListModal(listId = null) {
        const modal = document.getElementById('listModal');
        const title = document.getElementById('listModalTitle');
        const form = document.getElementById('listForm');
        const input = document.getElementById('listTitle');
        const idInput = document.getElementById('listId');

        if (listId) {
            const list = this.lists.find(l => l.id == listId);
            title.textContent = 'Editar Lista';
            input.value = list.title;
            idInput.value = listId;
        } else {
            title.textContent = 'Nova Lista';
            form.reset();
        }

        this.openModal('listModal');
        input.focus();
    }

    async handleListSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const listId = formData.get('listId');
        const title = formData.get('listTitle');

        if (!title.trim()) return;

        try {
            const url = listId ? `${this.apiBase}lists/${listId}` : `${this.apiBase}lists`;
            const method = listId ? 'PUT' : 'POST';
            const body = { title, board_id: this.boardId };

            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            if (!response.ok) throw new Error('Erro ao salvar lista');

            this.closeModal('listModal');
            await this.loadLists();
            this.showSuccess(listId ? 'Lista atualizada!' : 'Lista criada!');

        } catch (error) {
            console.error('Erro ao salvar lista:', error);
            this.showError('Erro ao salvar a lista');
        }
    }

    editList(listId) {
        this.openListModal(listId);
    }

    deleteList(listId) {
        const list = this.lists.find(l => l.id == listId);
        this.confirmDeleteAction(
            `Excluir a lista "${list.title}"?`,
            async () => {
                try {
                    const response = await fetch(`${this.apiBase}lists/${listId}`, {
                        method: 'DELETE'
                    });

                    if (!response.ok) throw new Error('Erro ao excluir lista');

                    await this.loadLists();
                    this.showSuccess('Lista excluída com sucesso!');

                } catch (error) {
                    console.error('Erro ao excluir lista:', error);
                    this.showError('Erro ao excluir a lista');
                }
            }
        );
    }

    // CRUD de Cards
    openCardModal(listId, cardId = null) {
        const modal = document.getElementById('cardModal');
        const title = document.getElementById('cardModalTitle');
        const form = document.getElementById('cardForm');
        const titleInput = document.getElementById('cardTitle');
        const descInput = document.getElementById('cardDescription');
        const idInput = document.getElementById('cardId');
        const listIdInput = document.getElementById('cardListId');

        if (cardId) {
            // Busca o card na memória (pode melhorar carregando via API)
            const card = this.findCardById(cardId);
            title.textContent = 'Editar Card';
            titleInput.value = card.title;
            descInput.value = card.description || '';
            idInput.value = cardId;
            listIdInput.value = card.list_id;
        } else {
            title.textContent = 'Novo Card';
            form.reset();
            listIdInput.value = listId;
        }

        this.openModal('cardModal');
        titleInput.focus();
    }

    findCardById(cardId) {
        for (const list of this.lists) {
            const cards = document.querySelectorAll(`[data-list-id="${list.id}"] .card`);
            // Implementação simplificada - idealmente carregaria via API
        }
        return null;
    }

    async handleCardSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const cardId = formData.get('cardId');
        const title = formData.get('cardTitle');
        const description = formData.get('cardDescription');
        const listId = formData.get('cardListId');

        if (!title.trim()) return;

        try {
            const url = cardId ? `${this.apiBase}cards/${cardId}` : `${this.apiBase}cards`;
            const method = cardId ? 'PUT' : 'POST';
            const body = { title, description, list_id: listId };

            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            if (!response.ok) throw new Error('Erro ao salvar card');

            this.closeModal('cardModal');
            await this.loadLists();
            this.showSuccess(cardId ? 'Card atualizado!' : 'Card criado!');

        } catch (error) {
            console.error('Erro ao salvar card:', error);
            this.showError('Erro ao salvar o card');
        }
    }

    editCard(cardId) {
        // Implementar edição direta ou via modal de visualização
        console.log('Editar card:', cardId);
    }

    editCardFromView() {
        if (this.currentCardId) {
            this.closeModal('viewCardModal');
            this.editCard(this.currentCardId);
        }
    }

    deleteCard(cardId) {
        this.confirmDeleteAction(
            'Excluir este card?',
            async () => {
                try {
                    const response = await fetch(`${this.apiBase}cards/${cardId}`, {
                        method: 'DELETE'
                    });

                    if (!response.ok) throw new Error('Erro ao excluir card');

                    await this.loadLists();
                    this.showSuccess('Card excluído com sucesso!');

                } catch (error) {
                    console.error('Erro ao excluir card:', error);
                    this.showError('Erro ao excluir o card');
                }
            }
        );
    }

    deleteCardFromView() {
        if (this.currentCardId) {
            this.closeModal('viewCardModal');
            this.deleteCard(this.currentCardId);
        }
    }

    // Utilitários de Modal
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Confirmação de exclusão
    confirmDeleteAction(message, callback) {
        document.getElementById('deleteMessage').textContent = message;
        this.deleteCallback = callback;
        this.openModal('deleteModal');
    }

    async confirmDelete() {
        if (this.deleteCallback) {
            await this.deleteCallback();
            this.deleteCallback = null;
        }
        this.closeModal('deleteModal');
    }

    // Utilitários
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    showSuccess(message) {
        // Implementar toast/notificação de sucesso
        console.log('✅', message);
    }

    showError(message) {
        // Implementar toast/notificação de erro
        console.error('❌', message);
        alert(message); // Temporário
    }
}

// Inicializa a aplicação
let boardManager;
document.addEventListener('DOMContentLoaded', () => {
    boardManager = new BoardManager();
});
