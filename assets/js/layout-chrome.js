export function measureLayoutBodyHeight(rightContentId = 'rightContent') {
    const rightContent = document.getElementById(rightContentId);
    if (!rightContent) {
        return null;
    }

    const footer = document.querySelector('.site-footer');
    const header = document.querySelector('.site-header');
    const footerHeight = footer ? footer.offsetHeight : 112;
    const headerHeight = header ? header.offsetHeight : (
        parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || 72
    );
    const gap = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--layout-chrome-gap')) || 16;
    const chromeHeight = footerHeight + headerHeight + (gap * 2);

    document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
    document.body.style.height = `${rightContent.offsetHeight + chromeHeight}px`;

    return {
        footerHeight,
        headerHeight,
        chromeHeight,
    };
}
