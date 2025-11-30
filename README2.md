DPHARMASOLUTION/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   └── Kernel.php
│   ├── Exports/
│   │   └── ReportsExport.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminCustomerController.php
│   │   │   ├── AdminOrderController.php
│   │   │   ├── AuthController.php
│   │   │   ├── ChatConversationController.php
│   │   │   ├── ChatViewController.php
│   │   │   ├── CheckLoginController.php
│   │   │   ├── ClientUpload.php
│   │   │   ├── Controller.php
│   │   │   ├── CustomerChatApiController.php
│   │   │   ├── CustomerChatController.php
│   │   │   ├── CustomerNotificationController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── PosController.php
│   │   │   ├── PrescriptionItemController.php
│   │   │   ├── ProductController.php
│   │   │   ├── ReportsController.php
│   │   │   └── SupplierController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       ├── Authenticate.php
│   │       ├── CustomerAuth.php
│   │       ├── EncryptCookies.php
│   │       ├── PreventRequestsDuringMaintenance.php
│   │       ├── RedirectIfAuthenticated.php
│   │       ├── TrimStrings.php
│   │       ├── TrustProxies.php
│   │       └── VerifyCsrfToken.php
│   │   └── Requests/
│   │       └── Kernel.php
│   ├── Models/
│   │   ├── CancelledOrder.php
│   │   ├── Category.php
│   │   ├── ChatMessage.php
│   │   ├── Conversation.php
│   │   ├── ConversationParticipant.php
│   │   ├── Customer.php
│   │   ├── CustomerChat.php
│   │   ├── CustomerNotification.php
│   │   ├── ExpiryDate.php
│   │   ├── Message.php
│   │   ├── MessageAttachment.php
│   │   ├── Notification.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── PosTransaction.php
│   │   ├── Prescription.php
│   │   ├── PrescriptionItem.php
│   │   ├── Product.php
│   │   ├── ProductBatch.php
│   │   ├── ReorderFlag.php
│   │   ├── Sale.php
│   │   ├── SaleItem.php
│   │   ├── StockMovement.php
│   │   ├── StockTransaction.php
│   │   ├── Supplier.php
│   │   ├── User.php
│   │   └── UserOnlineStatus.php
│   ├── Notifications/
│   ├── Providers/
│   └── Services/
│       ├── BatchExpirationService.php
│       ├── DuplicateDetectionService.php
│       ├── FileEncryptionService.php
│       ├── NotificationService.php
│       └── StockService.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   ├── seeders/
│   ├── .gitignore
│   ├── database.sql
│   └── database.sqlite
├── public/
│   ├── css/
│   │   └── customer/
│   │       ├── clienthome.css
│   │       ├── contactus.css
│   │       ├── customer.css
│   │       ├── feedback.css
│   │       ├── header.css
│   │       ├── messages.css
│   │       ├── notification.css
│   │       ├── products.css
│   │       ├── profile.css
│   │       ├── uploads.css
│   │       ├── admincustomer.css
│   │       ├── admindashboard.css
│   │       ├── adminorders.css
│   │       ├── dashboard.css
│   │       ├── inventory.css
│   │       ├── orders.css
│   │       ├── pos.css
│   │       ├── prescription.css
│   │       ├── products.css
│   │       ├── profile.css
│   │       ├── reports.css
│   │       ├── sales.css
│   │       ├── style.css
│   │       └── suppliers.css
│   ├── images/
│   ├── js/
│   ├── storage/
│   │   ├── chat-attachments/
│   │   ├── prescriptions/
│   │   └── qrcodes/
│   ├── .gitignore
│   ├── .htaccess
│   ├── favicon.ico
│   ├── index.php
│   ├── php_errors.log
│   └── robots.txt
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       │   ├── admin-footer.blade.php
│       │   ├── admin-header.blade.php
│       │   └── admin-profile.blade.php
│       ├── chat/
│       │   └── chatblade.php
│       ├── client/
│       │   ├── client-footer.blade.php
│       │   ├── client-header.blade.php
│       │   ├── contact-us.blade.php
│       │   ├── feedback.blade.php
│       │   ├── home.blade.php
│       │   ├── login.blade.php
│       │   ├── messages.blade.php
│       │   ├── notifications.blade.php
│       │   ├── preorder-confirmation.blade.php
│       │   ├── prescription-status.blade.php
│       │   ├── products.blade.php
│       │   ├── profile.blade.php
│       │   ├── qr-display.blade.php
│       │   ├── signup1.blade.php
│       │   ├── signup2.blade.php
│       │   └── uploads.blade.php
│       ├── customer/
│       │   └── customer.blade.php
│       ├── dashboard/
│       │   └── dashboard.blade.php
│       ├── inventory/
│       │   ├── batches-modal.blade.php
│       │   └── inventory.blade.php
│       ├── orders/
│       │   └── orders.blade.php
│       ├── pos/
│       │   └── pos.blade.php
│       ├── products/
│       │   ├── batches-modal.blade.php
│       │   └── batches.blade.php
│       ├── reports/
│       │   ├── pdf.blade.php
│       │   └── reports.blade.php
│       └── sales/
│           └── sales.blade.php
├── routes/
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
├── vendor/
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── .htmrc
├── _ide_helper.php
├── artisan
├── composer.json
├── composer.lock
├── groupfunction
├── LICENSE
├── name('dashboard')
├── package.json
└── package-lock.json



// Add this enhanced viewer class to your orders.blade.php script section
class EnhancedPrescriptionViewer {
    constructor() {
        this.state = {
            scale: 1,
            minScale: 0.5,
            maxScale: 5,
            translateX: 0,
            translateY: 0,
            isDragging: false,
            startX: 0,
            startY: 0,
            currentImage: null,
            rotation: 0
        };
        
        this.createViewerModal();
    }

    createViewerModal() {
        // Remove existing modal if present
        const existingModal = document.getElementById('enhancedPrescriptionViewer');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'enhancedPrescriptionViewer';
        modal.className = 'enhanced-viewer-modal';
        modal.innerHTML = `
            <div class="enhanced-viewer-overlay"></div>
            <div class="enhanced-viewer-container">
                <div class="enhanced-viewer-header">
                    <div class="viewer-title">Prescription Viewer</div>
                    <div class="viewer-controls">
                        <button class="viewer-btn" id="rotateLeftBtn" title="Rotate Left">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2.5 2v6h6M2.66 15.57a10 10 0 1 0 .57-8.38"/>
                            </svg>
                        </button>
                        <button class="viewer-btn" id="rotateRightBtn" title="Rotate Right">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                            </svg>
                        </button>
                        <button class="viewer-btn" id="zoomOutBtn" title="Zoom Out (-)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M8 11h6M21 21l-4.35-4.35"/>
                            </svg>
                        </button>
                        <span class="zoom-level" id="zoomLevel">100%</span>
                        <button class="viewer-btn" id="zoomInBtn" title="Zoom In (+)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M11 8v6M8 11h6M21 21l-4.35-4.35"/>
                            </svg>
                        </button>
                        <button class="viewer-btn" id="resetViewBtn" title="Reset View">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                <path d="M21 3v5h-5"/>
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M3 21v-5h5"/>
                            </svg>
                        </button>
                        <button class="viewer-btn" id="fullscreenBtn" title="Fullscreen">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                            </svg>
                        </button>
                        <button class="viewer-btn" id="downloadViewerBtn" title="Download">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                        </button>
                    </div>
                    <button class="viewer-close" id="closeEnhancedViewer" title="Close (ESC)">✕</button>
                </div>
                
                <div class="enhanced-viewer-content" id="viewerContent">
                    <div class="viewer-loading" id="viewerLoading">
                        <div class="loading-spinner"></div>
                        <p>Loading prescription...</p>
                    </div>
                    <div class="viewer-image-wrapper" id="imageWrapper">
                        <img id="prescriptionImage" alt="Prescription" draggable="false">
                    </div>
                </div>
                
                <div class="enhanced-viewer-footer">
                    <div class="viewer-instructions">
                        <span>💡 <strong>Tip:</strong> Use scroll wheel to zoom, drag to pan, or use keyboard shortcuts</span>
                    </div>
                    <div class="keyboard-shortcuts">
                        <span><kbd>+</kbd> Zoom In</span>
                        <span><kbd>-</kbd> Zoom Out</span>
                        <span><kbd>R</kbd> Reset</span>
                        <span><kbd>←</kbd><kbd>→</kbd> Rotate</span>
                        <span><kbd>ESC</kbd> Close</span>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.bindViewerEvents();
        this.addViewerStyles();
    }

    addViewerStyles() {
        if (document.getElementById('enhancedViewerStyles')) return;

        const styles = document.createElement('style');
        styles.id = 'enhancedViewerStyles';
        styles.textContent = `
            .enhanced-viewer-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                animation: fadeIn 0.2s ease-in-out;
            }

            .enhanced-viewer-modal.active {
                display: block;
            }

            .enhanced-viewer-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.95);
            }

            .enhanced-viewer-container {
                position: relative;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
            }

            .enhanced-viewer-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 15px 20px;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .viewer-title {
                color: white;
                font-size: 18px;
                font-weight: 600;
            }

            .viewer-controls {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .viewer-btn {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }

            .viewer-btn:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-2px);
            }

            .viewer-btn:active {
                transform: translateY(0);
            }

            .zoom-level {
                color: white;
                font-size: 14px;
                font-weight: 600;
                min-width: 50px;
                text-align: center;
            }

            .viewer-close {
                background: rgba(239, 68, 68, 0.2);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }

            .viewer-close:hover {
                background: rgba(239, 68, 68, 0.4);
            }

            .enhanced-viewer-content {
                flex: 1;
                position: relative;
                overflow: hidden;
                background: #000;
            }

            .viewer-loading {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                color: white;
            }

            .viewer-image-wrapper {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: grab;
            }

            .viewer-image-wrapper.dragging {
                cursor: grabbing;
            }

            #prescriptionImage {
                max-width: none;
                max-height: none;
                user-select: none;
                transition: transform 0.1s ease-out;
                image-rendering: high-quality;
            }

            .enhanced-viewer-footer {
                padding: 12px 20px;
                background: rgba(255, 255, 255, 0.05);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .viewer-instructions {
                color: rgba(255, 255, 255, 0.8);
                font-size: 13px;
            }

            .keyboard-shortcuts {
                display: flex;
                gap: 15px;
                color: rgba(255, 255, 255, 0.7);
                font-size: 12px;
            }

            .keyboard-shortcuts kbd {
                background: rgba(255, 255, 255, 0.1);
                padding: 2px 6px;
                border-radius: 4px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                font-family: monospace;
                font-size: 11px;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }

            @media (max-width: 768px) {
                .enhanced-viewer-header {
                    flex-direction: column;
                    gap: 10px;
                    padding: 10px;
                }

                .viewer-controls {
                    flex-wrap: wrap;
                    justify-content: center;
                }

                .keyboard-shortcuts {
                    display: none;
                }
            }
        `;

        document.head.appendChild(styles);
    }

    bindViewerEvents() {
        const modal = document.getElementById('enhancedPrescriptionViewer');
        const closeBtn = document.getElementById('closeEnhancedViewer');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const resetBtn = document.getElementById('resetViewBtn');
        const rotateLeftBtn = document.getElementById('rotateLeftBtn');
        const rotateRightBtn = document.getElementById('rotateRightBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const downloadBtn = document.getElementById('downloadViewerBtn');
        const imageWrapper = document.getElementById('imageWrapper');
        const image = document.getElementById('prescriptionImage');

        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        // Zoom controls
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => this.zoomIn());
        }
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => this.zoomOut());
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.reset());
        }

        // Rotation controls
        if (rotateLeftBtn) {
            rotateLeftBtn.addEventListener('click', () => this.rotate(-90));
        }
        if (rotateRightBtn) {
            rotateRightBtn.addEventListener('click', () => this.rotate(90));
        }

        // Fullscreen
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        }

        // Download
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => this.download());
        }

        // Mouse wheel zoom
        if (imageWrapper) {
            imageWrapper.addEventListener('wheel', (e) => this.handleWheel(e));
        }

        // Drag to pan
        if (imageWrapper && image) {
            imageWrapper.addEventListener('mousedown', (e) => this.startDrag(e));
            imageWrapper.addEventListener('mousemove', (e) => this.drag(e));
            imageWrapper.addEventListener('mouseup', () => this.endDrag());
            imageWrapper.addEventListener('mouseleave', () => this.endDrag());

            // Touch events for mobile
            imageWrapper.addEventListener('touchstart', (e) => this.handleTouchStart(e));
            imageWrapper.addEventListener('touchmove', (e) => this.handleTouchMove(e));
            imageWrapper.addEventListener('touchend', () => this.endDrag());
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));

        // Close on overlay click
        const overlay = modal.querySelector('.enhanced-viewer-overlay');
        if (overlay) {
            overlay.addEventListener('click', () => this.close());
        }
    }

    open(imageUrl, filename = 'Prescription') {
        const modal = document.getElementById('enhancedPrescriptionViewer');
        const loading = document.getElementById('viewerLoading');
        const image = document.getElementById('prescriptionImage');
        const title = modal.querySelector('.viewer-title');

        if (!modal || !image) return;

        // Set title
        if (title) {
            title.textContent = filename;
        }

        // Reset state
        this.reset();

        // Show modal and loading
        modal.classList.add('active');
        if (loading) loading.style.display = 'block';

        // Store current image URL for download
        this.state.currentImage = imageUrl;

        // Load image
        const img = new Image();
        img.onload = () => {
            image.src = imageUrl;
            if (loading) loading.style.display = 'none';
            
            // Auto-fit image to screen
            this.autoFit();
        };

        img.onerror = () => {
            if (loading) {
                loading.innerHTML = `
                    <div style="color: #ef4444;">
                        <p>Failed to load prescription image</p>
                        <button onclick="enhancedViewer.close()" class="viewer-btn" style="margin-top: 10px;">
                            Close
                        </button>
                    </div>
                `;
            }
        };

        img.src = imageUrl;
    }

    close() {
        const modal = document.getElementById('enhancedPrescriptionViewer');
        if (modal) {
            modal.classList.remove('active');
        }
        this.reset();
    }

    autoFit() {
        const image = document.getElementById('prescriptionImage');
        const wrapper = document.getElementById('imageWrapper');
        
        if (!image || !wrapper) return;

        const wrapperRect = wrapper.getBoundingClientRect();
        const imageRect = image.getBoundingClientRect();

        // Calculate scale to fit image in viewport with padding
        const scaleX = (wrapperRect.width * 0.9) / imageRect.width;
        const scaleY = (wrapperRect.height * 0.9) / imageRect.height;
        
        this.state.scale = Math.min(scaleX, scaleY, 1); // Don't scale up initially
        this.updateTransform();
    }

    zoomIn() {
        if (this.state.scale < this.state.maxScale) {
            this.state.scale = Math.min(this.state.scale * 1.2, this.state.maxScale);
            this.updateTransform();
        }
    }

    zoomOut() {
        if (this.state.scale > this.state.minScale) {
            this.state.scale = Math.max(this.state.scale / 1.2, this.state.minScale);
            this.updateTransform();
        }
    }

    rotate(degrees) {
        this.state.rotation = (this.state.rotation + degrees) % 360;
        this.updateTransform();
    }

    reset() {
        this.state.scale = 1;
        this.state.translateX = 0;
        this.state.translateY = 0;
        this.state.rotation = 0;
        this.state.isDragging = false;
        this.updateTransform();
    }

    handleWheel(e) {
        e.preventDefault();
        
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        const newScale = this.state.scale * delta;

        if (newScale >= this.state.minScale && newScale <= this.state.maxScale) {
            this.state.scale = newScale;
            this.updateTransform();
        }
    }

    startDrag(e) {
        this.state.isDragging = true;
        this.state.startX = e.clientX - this.state.translateX;
        this.state.startY = e.clientY - this.state.translateY;

        const wrapper = document.getElementById('imageWrapper');
        if (wrapper) {
            wrapper.classList.add('dragging');
        }
    }

    drag(e) {
        if (!this.state.isDragging) return;

        e.preventDefault();
        this.state.translateX = e.clientX - this.state.startX;
        this.state.translateY = e.clientY - this.state.startY;
        this.updateTransform();
    }

    endDrag() {
        this.state.isDragging = false;
        
        const wrapper = document.getElementById('imageWrapper');
        if (wrapper) {
            wrapper.classList.remove('dragging');
        }
    }

    handleTouchStart(e) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            this.state.isDragging = true;
            this.state.startX = touch.clientX - this.state.translateX;
            this.state.startY = touch.clientY - this.state.translateY;
        }
    }

    handleTouchMove(e) {
        if (!this.state.isDragging || e.touches.length !== 1) return;

        e.preventDefault();
        const touch = e.touches[0];
        this.state.translateX = touch.clientX - this.state.startX;
        this.state.translateY = touch.clientY - this.state.startY;
        this.updateTransform();
    }

    handleKeyboard(e) {
        const modal = document.getElementById('enhancedPrescriptionViewer');
        if (!modal || !modal.classList.contains('active')) return;

        switch(e.key) {
            case 'Escape':
                this.close();
                break;
            case '+':
            case '=':
                this.zoomIn();
                break;
            case '-':
            case '_':
                this.zoomOut();
                break;
            case 'r':
            case 'R':
                this.reset();
                break;
            case 'ArrowLeft':
                this.rotate(-90);
                break;
            case 'ArrowRight':
                this.rotate(90);
                break;
            case 'f':
            case 'F':
                this.toggleFullscreen();
                break;
        }
    }

    toggleFullscreen() {
        const modal = document.getElementById('enhancedPrescriptionViewer');
        
        if (!document.fullscreenElement) {
            modal.requestFullscreen().catch(err => {
                console.warn('Fullscreen request failed:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    download() {
        if (!this.state.currentImage) return;

        const link = document.createElement('a');
        link.href = this.state.currentImage;
        link.download = 'prescription.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    updateTransform() {
        const image = document.getElementById('prescriptionImage');
        const zoomLevel = document.getElementById('zoomLevel');

        if (image) {
            image.style.transform = `
                translate(${this.state.translateX}px, ${this.state.translateY}px) 
                scale(${this.state.scale}) 
                rotate(${this.state.rotation}deg)
            `;
        }

        if (zoomLevel) {
            zoomLevel.textContent = `${Math.round(this.state.scale * 100)}%`;
        }
    }
}

// Initialize the enhanced viewer
window.enhancedViewer = new EnhancedPrescriptionViewer();
