export default function(key) {
    let value = _.get(key,this.$page.props.user.language);
    console.log(value);
    if (value) {
        return value;
    } else {
        return key;
    }
}
