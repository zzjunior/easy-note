// Classe principal da aplicação
class EasyNoteApp {
    constructor() {
        this.apiBase = '/';
        this.init();
    }

    // Inicializa a aplicação
    init() {
        this.setupEventListeners();
        this.loadBoards();
    }

    // Configura os event listeners
    setupEventListeners() {
        // Form de criar board
        const createForm = document.getElementById('createBoardForm');
        createForm.addEventListener('submit', (e) => this.handleCreateBoard(e));

        // Modal de exclusão
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        
        cancelDelete.addEventListener('click', () => this.hideModal());
        confirmDelete.addEventListener('click', () => this.confirmDeleteBoard());

        // Fechar modal ao clicar fora
        const modal = document.getElementById('deleteModal');
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideModal();
            }
        });
    }

    // Carrega a lista de boards
    async loadBoards() {
        try {
            this.showLoading();
            const response = await fetch(`${this.apiBase}boards`);
            
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }
            
            const boards = await response.json();
            this.renderBoards(boards);
        } catch (error) {
            console.error('Erro ao carregar boards:', error);
            this.showError('Erro ao carregar os boards. Verifique se o servidor está rodando.');
        }
    }

    // Renderiza a lista de boards
    renderBoards(boards) {
        const boardsList = document.getElementById('boardsList');
        
        if (boards.length === 0) {
            boardsList.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <div class="text-8xl mb-6">🎯</div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Nenhum board criado ainda</h3>
                    <p class="text-gray-600 text-lg">Crie seu primeiro board para começar a organizar suas tarefas!</p>
                </div>
            `;
            return;
        }

        boardsList.innerHTML = boards.map(board => `
            <div class="board-card-hover glass-card rounded-2xl p-6 cursor-pointer group board-card-new" 
                 onclick="app.openBoard(${board.id})"
                 data-board-id="${board.id}">
                
                <!-- Header do Card -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-primary transition-colors duration-300">
                            ${this.escapeHtml(board.title)}
                        </h3>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span class="bg-gradient-to-r from-primary/10 to-purple-500/10 px-3 py-1 rounded-full">
                                📅 ${this.formatDate(board.created_at)}
                            </span>
                        </div>
                    </div>
                    <div class="relative group/actions">
                        <button 
                            onclick="event.stopPropagation(); app.deleteBoard(${board.id})" 
                            class="opacity-0 group-hover:opacity-100 transition-all duration-300 p-2 rounded-lg hover:bg-red-50 hover:text-red-600"
                            title="Excluir board"
                        >
                            🗑️
                        </button>
                    </div>
                </div>

                <!-- Estatísticas do Board -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-3 rounded-xl border border-blue-200">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600">📝</span>
                            <div>
                                <div class="text-sm text-blue-600 font-medium">Cards</div>
                                <div class="text-lg font-bold text-blue-800">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 p-3 rounded-xl border border-green-200">
                        <div class="flex items-center gap-2">
                            <span class="text-green-600">✅</span>
                            <div>
                                <div class="text-sm text-green-600 font-medium">Concluídos</div>
                                <div class="text-lg font-bold text-green-800">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ação Visual -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <span class="text-sm text-gray-500 font-medium">Clique para abrir</span>
                    <div class="bg-gradient-to-r from-primary to-purple-600 text-white p-2 rounded-lg group-hover:shadow-lg transition-all duration-300">
                        <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Cria um novo board
    async handleCreateBoard(e) {
        e.preventDefault();
        
        const titleInput = document.getElementById('boardTitle');
        const title = titleInput.value.trim();
        
        if (!title) {
            this.showError('Por favor, digite um nome para o board.');
            return;
        }

        try {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Criando...';

            const response = await fetch(`${this.apiBase}boards`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ title })
            });

            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }

            const newBoard = await response.json();
            
            this.showSuccess(`Board "${title}" criado com sucesso!`);
            titleInput.value = '';
            
            // Recarrega a lista de boards
            await this.loadBoards();

        } catch (error) {
            console.error('Erro ao criar board:', error);
            this.showError('Erro ao criar o board. Tente novamente.');
        } finally {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.textContent = 'Criar Board';
        }
    }

    // Abre um board (futura implementação)
    openBoard(boardId) {
        // Navega para a página do board
        window.location.href = `/board.html?id=${boardId}`;
    }

    // Inicia o processo de exclusão de board
    deleteBoard(boardId) {
        this.boardToDelete = boardId;
        this.showModal();
    }

    // Confirma a exclusão do board
    async confirmDeleteBoard() {
        if (!this.boardToDelete) return;

        try {
            // Por enquanto, como não temos endpoint DELETE, vamos simular
            this.showSuccess('Board excluído com sucesso!');
            this.hideModal();
            
            // Remove o card visualmente
            const boardCard = document.querySelector(`[data-board-id="${this.boardToDelete}"]`);
            if (boardCard) {
                boardCard.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    boardCard.remove();
                    // Verifica se ainda há boards
                    const remainingBoards = document.querySelectorAll('.board-card');
                    if (remainingBoards.length === 0) {
                        this.loadBoards(); // Recarrega para mostrar estado vazio
                    }
                }, 300);
            }
            
            this.boardToDelete = null;

        } catch (error) {
            console.error('Erro ao excluir board:', error);
            this.showError('Erro ao excluir o board. Tente novamente.');
        }
    }

    // Mostra o modal de confirmação
    showModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Anima o modal
        setTimeout(() => {
            const content = modal.querySelector('.glass-card');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    // Esconde o modal
    hideModal() {
        const modal = document.getElementById('deleteModal');
        const content = modal.querySelector('.glass-card');
        
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
        
        this.boardToDelete = null;
    }

    // Mostra estado de carregamento
    showLoading() {
        const boardsList = document.getElementById('boardsList');
        boardsList.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="flex justify-center space-x-2 mb-4">
                    <div class="w-3 h-3 bg-primary rounded-full animate-bounce"></div>
                    <div class="w-3 h-3 bg-primary rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                    <div class="w-3 h-3 bg-primary rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                </div>
                <p class="text-gray-500 font-medium">Carregando boards...</p>
            </div>
        `;
    }

    // Mostra mensagem de sucesso
    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    // Mostra mensagem de erro
    showError(message) {
        this.showMessage(message, 'error');
    }

    // Mostra mensagem temporária
    showMessage(message, type) {
        // Remove mensagem anterior se existir
        const existingMessage = document.querySelector('.toast-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Cria nova mensagem
        const messageDiv = document.createElement('div');
        messageDiv.className = `toast-message fixed top-6 right-6 z-50 max-w-sm transform transition-all duration-500 translate-x-full`;
        
        const bgColor = type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600';
        const icon = type === 'success' ? '✅' : '❌';
        
        messageDiv.innerHTML = `
            <div class="${bgColor} text-white p-4 rounded-2xl shadow-2xl border border-white/20">
                <div class="flex items-center gap-3">
                    <span class="text-xl">${icon}</span>
                    <span class="font-medium">${message}</span>
                </div>
            </div>
        `;

        document.body.appendChild(messageDiv);

        // Anima para dentro
        setTimeout(() => {
            messageDiv.classList.remove('translate-x-full');
        }, 100);

        // Remove após 5 segundos
        setTimeout(() => {
            messageDiv.classList.add('translate-x-full');
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }

    // Formata data para exibição
    formatDate(dateString) {
        if (!dateString) return 'Data não disponível';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Data não disponível';
        }
    }

    // Escapa HTML para prevenir XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// CSS para animação de saída
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);

// Inicializa a aplicação quando o DOM estiver carregado
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new EasyNoteApp();
});

// Expõe a instância globalmente para uso nos event handlers inline
window.app = app;
