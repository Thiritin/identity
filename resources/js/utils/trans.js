export default function (key, lang = null) {
    let value = "";
    if (lang) {
        value = _.get(key, lang);
    } else {
        value = _.get(key, this.$page.props.user.language);
    }
    if (value) {
        return value;
    } else {
        return key;
    }
}
