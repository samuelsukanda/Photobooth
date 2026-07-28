import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = 'https://photobooth.weddingplanner.web.id';

const photoListTrend = new Trend('photo_list_duration');
const photoUploadTrend = new Trend('photo_upload_duration');
const errorRate = new Rate('errors');

export let options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    errors: ['rate<0.05'],
    http_req_duration: ['p(95)<2000'],
    photo_list_duration: ['p(95)<1500'],
    photo_upload_duration: ['p(95)<3000'],
  },
};

function randomBase64Image() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
  let result = '';
  const bytes = 5000;
  for (let i = 0; i < bytes; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return `data:image/jpeg;base64,${result}`;
}

export default function () {
  group('GET /api/photos', function () {
    const res = http.get(`${BASE_URL}/api/photos`);
    photoListTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'photos status 200': (r) => r.status === 200,
      'photos response is array': (r) => Array.isArray(r.json()),
    });
  });

  group('POST /api/photos', function () {
    const payload = JSON.stringify({
      image: randomBase64Image(),
      guest_token: 'demo-token',
    });

    const res = http.post(`${BASE_URL}/api/photos`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    photoUploadTrend.add(res.timings.duration);
    errorRate.add(res.status !== 201 && res.status !== 422);
    check(res, {
      'upload accepted (200)': (r) => r.status === 200,
    });
  });

  sleep(1);
}
