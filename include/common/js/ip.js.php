function isIP(ip) {
  if (!ip) return false;

  let cidr = ip.split('/');
  if (cidr.length !== 2) {
    return false;
  }

  let arrIp = cidr[0].split('.');
  if (arrIp.length !== 4) {
    return false;
  }

  for (let oct of arrIp) {
    if (Number(oct) < 0 || Number(oct) > 255){
        return false;
    }
  }

  if (Number(cidr[1]) < 16 || Number(cidr[1]) > 32){
    return false;
  }

  return true;
}
