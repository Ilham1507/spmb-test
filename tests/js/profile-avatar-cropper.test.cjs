const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/profile-avatar-cropper.js'), 'utf8');

function cropper() {
    const context = vm.createContext({});
    vm.runInContext(source, context);
    const crop = context.profileAvatarCropper('character_7');
    crop.image = { width: 560, height: 280 }; crop.hasImage = true;
    crop.$nextTick = fn => fn();
    crop.$refs = { crop: { value: '' }, canvas: {
        width: 280, height: 280, setPointerCapture() {},
        getBoundingClientRect: () => ({ left: 10, top: 20, width: 560, height: 560 }),
        getContext: () => ({ clearRect() {}, drawImage() {} }),
        toDataURL: () => 'data:image/jpeg;base64,cropped',
    } };
    return crop;
}
function pointer(id, x, y, pointerType = 'touch') {
    return { pointerId: id, clientX: 10 + x * 2, clientY: 20 + y * 2, pointerType, button: 0, preventDefault() {} };
}

test('one finger / click-hold drag respects responsive canvas coordinates', () => {
    for (const type of ['touch', 'mouse', 'pen']) {
        const c = cropper();
        c.pointerDown(pointer(1, 140, 140, type));
        c.pointerMove(pointer(1, 180, 150, type));
        assert.equal(c.offsetX, 40); assert.equal(c.offsetY, 0);
        c.pointerUp(pointer(1, 180, 150, type));
        c.pointerMove(pointer(1, 220, 150, type));
        assert.equal(c.offsetX, 40); assert.equal(c.gesture, null);
    }
});
test('pinch zoom keeps its focal point and rebases to one-finger drag', () => {
    const c = cropper();
    c.pointerDown(pointer(1, 100, 140)); c.pointerDown(pointer(2, 180, 140));
    c.pointerMove(pointer(2, 260, 140));
    assert.equal(c.zoom, 2); assert.equal(c.offsetX, 40);
    c.pointerUp(pointer(2, 260, 140));
    c.pointerMove(pointer(1, 120, 140));
    assert.equal(c.offsetX, 60); assert.equal(c.zoom, 2);
});
test('wheel / trackpad zoom is bounded, pointer anchored and prevents page scroll', () => {
    const c = cropper(); let prevented = false;
    c.wheel({ ...pointer(1, 200, 140), deltaY: -Math.log(2) / .002, deltaMode: 0, preventDefault() { prevented = true; } });
    assert.equal(prevented, true); assert.ok(Math.abs(c.zoom - 2) < .000001);
    assert.ok(Math.abs(c.offsetX + 60) < .000001);
    c.zoomBy(100); assert.equal(c.zoom, 3);
    c.zoomBy(.001); assert.equal(c.zoom, 1);
});
test('crop never leaves blank edges even after extreme dragging', () => {
    const c = cropper(); c.zoomBy(2);
    c.pointerDown(pointer(1, 140, 140)); c.pointerMove(pointer(1, 2000, -1000));
    assert.equal(c.offsetX, 420); assert.equal(c.offsetY, -140);
    c.resetCrop(); assert.equal(c.zoom, 1); assert.equal(c.offsetX, 0); assert.equal(c.offsetY, 0);
    assert.equal(Object.keys(c.pointers).length, 0);
});
test('pointer cancellation, keyboard controls and selected crop submission', () => {
    const c = cropper(); c.zoomBy(2);
    c.pointerDown(pointer(1, 140, 140)); c.pointerUp(pointer(1, 140, 140));
    assert.equal(c.gesture, null);
    c.keyMove({ key: 'ArrowRight', preventDefault() {} }); assert.equal(c.offsetX, 10);
    c.prepareCrop(); assert.equal(c.$refs.crop.value, 'data:image/jpeg;base64,cropped');
    c.hasImage = false; c.prepareCrop(); assert.equal(c.$refs.crop.value, '');
    assert.equal(c.avatarPreviewStyle(), '--avatar-x:25%;--avatar-y:100%');
});
