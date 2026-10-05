function profileAvatarCropper(avatarChoice = 'character_1') {
    return {
        photoOpen: false, avatarChoice, image: null, hasImage: false,
        zoom: 1, offsetX: 0, offsetY: 0, pointers: {}, gesture: null,
        loadFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Pilih gambar JPG, PNG, atau WEBP.'); return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran foto maksimal 5 MB.'); return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                const image = new Image();
                image.onload = () => {
                    this.image = image; this.hasImage = true; this.avatarChoice = null;
                    this.resetCrop();
                };
                image.onerror = () => alert('Gambar tidak dapat dibuka. Pilih file lain.');
                image.src = reader.result;
            };
            reader.readAsDataURL(file);
        },
        resetCrop() {
            this.zoom = 1; this.offsetX = 0; this.offsetY = 0;
            this.pointers = {}; this.gesture = null;
            this.$nextTick(() => this.draw());
        },
        point(event) {
            const canvas = this.$refs.canvas, rect = canvas.getBoundingClientRect();
            return { x: (event.clientX - rect.left) * canvas.width / rect.width,
                y: (event.clientY - rect.top) * canvas.height / rect.height };
        },
        startGesture() {
            const points = Object.values(this.pointers).slice(0, 2);
            if (!points.length) { this.gesture = null; return; }
            const middle = points.length === 2
                ? { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 }
                : points[0];
            this.gesture = { middle, distance: points.length === 2
                ? Math.hypot(points[1].x - points[0].x, points[1].y - points[0].y) : 0,
                zoom: this.zoom, x: this.offsetX, y: this.offsetY };
        },
        pointerDown(event) {
            if (!this.hasImage || (event.pointerType === 'mouse' && event.button !== 0)) return;
            event.preventDefault();
            this.$refs.canvas.setPointerCapture(event.pointerId);
            this.pointers[event.pointerId] = this.point(event);
            this.startGesture();
        },
        pointerMove(event) {
            if (!this.gesture || !Object.hasOwn(this.pointers, event.pointerId)) return;
            this.pointers[event.pointerId] = this.point(event);
            const points = Object.values(this.pointers).slice(0, 2), start = this.gesture;
            if (points.length === 2 && start.distance > 0) {
                const middle = { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 };
                this.zoom = Math.max(1, Math.min(3, start.zoom * Math.hypot(points[1].x - points[0].x, points[1].y - points[0].y) / start.distance));
                const ratio = this.zoom / start.zoom, c = this.$refs.canvas;
                this.offsetX = middle.x - c.width / 2 - (start.middle.x - c.width / 2 - start.x) * ratio;
                this.offsetY = middle.y - c.height / 2 - (start.middle.y - c.height / 2 - start.y) * ratio;
            } else {
                this.offsetX = start.x + points[0].x - start.middle.x;
                this.offsetY = start.y + points[0].y - start.middle.y;
            }
            this.draw();
        },
        pointerUp(event) {
            delete this.pointers[event.pointerId];
            this.startGesture();
        },
        zoomAt(nextZoom, point) {
            if (!this.hasImage) return;
            const c = this.$refs.canvas, ratio = Math.max(1, Math.min(3, nextZoom)) / this.zoom;
            this.zoom *= ratio;
            this.offsetX = point.x - c.width / 2 - (point.x - c.width / 2 - this.offsetX) * ratio;
            this.offsetY = point.y - c.height / 2 - (point.y - c.height / 2 - this.offsetY) * ratio;
            this.draw(); this.startGesture();
        },
        zoomBy(factor) {
            const c = this.$refs.canvas;
            this.zoomAt(this.zoom * factor, { x: c.width / 2, y: c.height / 2 });
        },
        wheel(event) {
            if (!this.hasImage) return;
            event.preventDefault();
            const delta = event.deltaY * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? 280 : 1);
            this.zoomAt(this.zoom * Math.exp(-delta * 0.002), this.point(event));
        },
        keyMove(event) {
            const moves = { ArrowLeft: [-10, 0], ArrowRight: [10, 0], ArrowUp: [0, -10], ArrowDown: [0, 10] };
            if (moves[event.key]) {
                event.preventDefault(); this.offsetX += moves[event.key][0]; this.offsetY += moves[event.key][1]; this.draw();
            } else if (['+', '=', '-'].includes(event.key)) {
                event.preventDefault(); this.zoomBy(event.key === '-' ? 1 / 1.15 : 1.15);
            }
        },
        draw() {
            if (!this.image || !this.$refs.canvas) return;
            const c = this.$refs.canvas, ctx = c.getContext('2d');
            const scale = Math.max(c.width / this.image.width, c.height / this.image.height) * this.zoom;
            const w = this.image.width * scale, h = this.image.height * scale;
            // Keep the crop fully covered: dragging cannot create blank edges.
            this.offsetX = Math.max(-(w - c.width) / 2, Math.min((w - c.width) / 2, this.offsetX));
            this.offsetY = Math.max(-(h - c.height) / 2, Math.min((h - c.height) / 2, this.offsetY));
            ctx.clearRect(0, 0, c.width, c.height);
            ctx.drawImage(this.image, (c.width - w) / 2 + this.offsetX, (c.height - h) / 2 + this.offsetY, w, h);
        },
        avatarPreviewStyle() {
            const index = (parseInt((this.avatarChoice || 'character_1').replace('character_', ''), 10) || 1) - 1;
            return `--avatar-x:${(index % 5) * 25}%;--avatar-y:${Math.floor(index / 5) * 100}%`;
        },
        prepareCrop() {
            this.draw();
            this.$refs.crop.value = this.hasImage ? this.$refs.canvas.toDataURL('image/jpeg', .9) : '';
        },
    };
}
