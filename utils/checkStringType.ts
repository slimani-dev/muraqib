export function checkStringType(input: string): "url" | "ip" | "neither" {
  const urlRegex = /^(https?:\/\/)?([\w-]+\.)+[\w-]+(\/[\w- ./?%&=]*)?$/i;
  const ipv4Regex = /^(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}$/;
  const ipv6Regex = /^([a-fA-F0-9:]+:+)+[a-fA-F0-9]+$/;

  if (ipv4Regex.test(input) || ipv6Regex.test(input)) {
    return "ip";
  } else if (urlRegex.test(input)) {
    return "url";
  } else {
    return "neither";
  }
}
