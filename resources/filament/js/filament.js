function copyToClipboard(value, notificationBody) {
    window.navigator.clipboard.writeText(value);
    new FilamentNotification()
        .icon('heroicon-o-clipboard')
        .iconColor('success')
        .title('Copied to clipboard')
        .body(notificationBody !== undefined ? notificationBody : value)
        .send();
}

window.addEventListener('copyToClipboard', (event) => copyToClipboard(event.detail.text));