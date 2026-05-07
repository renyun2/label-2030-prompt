// 自定义对话框系统
class Dialog {
    constructor() {
        this.createOverlay();
    }

    createOverlay() {
        if (document.getElementById('customDialogOverlay')) return;
        
        const overlay = document.createElement('div');
        overlay.id = 'customDialogOverlay';
        overlay.className = 'dialog-overlay';
        overlay.innerHTML = `
            <div class="dialog-box">
                <div class="dialog-header">
                    <h3 class="dialog-title"></h3>
                </div>
                <div class="dialog-body">
                    <p class="dialog-message"></p>
                </div>
                <div class="dialog-footer">
                    <button class="dialog-btn dialog-btn-cancel">取消</button>
                    <button class="dialog-btn dialog-btn-confirm">确定</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        // 点击遮罩层关闭
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.close();
            }
        });
    }

    show(options) {
        const {
            title = '提示',
            message = '',
            confirmText = '确定',
            cancelText = '取消',
            onConfirm = null,
            onCancel = null,
            type = 'confirm' // 'confirm' 或 'alert'
        } = options;

        const overlay = document.getElementById('customDialogOverlay');
        const titleEl = overlay.querySelector('.dialog-title');
        const messageEl = overlay.querySelector('.dialog-message');
        const confirmBtn = overlay.querySelector('.dialog-btn-confirm');
        const cancelBtn = overlay.querySelector('.dialog-btn-cancel');

        titleEl.textContent = title;
        messageEl.textContent = message;
        confirmBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        // 移除旧的事件监听器
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        // 如果是alert类型，隐藏取消按钮
        if (type === 'alert') {
            newCancelBtn.style.display = 'none';
        } else {
            newCancelBtn.style.display = 'inline-block';
        }

        // 添加新的事件监听器
        newConfirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            this.close();
        });

        newCancelBtn.addEventListener('click', () => {
            if (onCancel) onCancel();
            this.close();
        });

        // ESC键关闭
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                this.close();
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);

        overlay.classList.add('show');
    }

    close() {
        const overlay = document.getElementById('customDialogOverlay');
        overlay.classList.remove('show');
    }

    alert(message, title = '提示') {
        this.show({
            title,
            message,
            type: 'alert',
            confirmText: '确定'
        });
    }

    confirm(message, title = '确认') {
        return new Promise((resolve) => {
            this.show({
                title,
                message,
                type: 'confirm',
                confirmText: '确定',
                cancelText: '取消',
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            });
        });
    }

    success(message, title = '成功') {
        this.show({
            title: '✓ ' + title,
            message,
            type: 'alert',
            confirmText: '确定'
        });
    }

    error(message, title = '错误') {
        this.show({
            title: '✗ ' + title,
            message,
            type: 'alert',
            confirmText: '确定'
        });
    }
}

// 创建全局实例
const dialog = new Dialog();

// Toast 提示
class Toast {
    constructor() {
        this.createContainer();
    }

    createContainer() {
        if (document.getElementById('toastContainer')) return;
        
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    show(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        }[type] || 'ℹ';

        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;

        container.appendChild(toast);

        // 动画显示
        setTimeout(() => toast.classList.add('show'), 10);

        // 自动隐藏
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    success(message, duration) {
        this.show(message, 'success', duration);
    }

    error(message, duration) {
        this.show(message, 'error', duration);
    }

    warning(message, duration) {
        this.show(message, 'warning', duration);
    }

    info(message, duration) {
        this.show(message, 'info', duration);
    }
}

// 创建全局实例
const toast = new Toast();
