import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = { vus: 10, duration: '15s' };

export default function () {
  let res = http.get('http://photobooth.test/api/photos');
  check(res, {
    'status is 200': (r) => r.status === 200,
  });
  console.log(`GET status: ${res.status}`);
  sleep(1);
}
