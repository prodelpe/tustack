export function companyUrl(slug) {
    return window.__COMPANY_URL__.replace('__SLUG__', slug)
}

export function buildLinkUrl(baseUrl, { techs = [], provs = [], excludeConsultancies = false, excludeRecruitment = false } = {}) {
    const params = new URLSearchParams()
    techs.forEach((t, i) => params.set(`tech[${i}]`, t))
    provs.forEach((p, i) => params.set(`prov[${i}]`, p))
    if (excludeConsultancies) params.set('excl_cons', '1')
    if (excludeRecruitment)   params.set('excl_rec', '1')
    const query = params.toString().replaceAll('%5B', '[').replaceAll('%5D', ']')
    return baseUrl + (query ? '?' + query : '')
}
