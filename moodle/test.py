import requests

API_URL = "https://test.getecho.io/moodle/filter/echo_url/api.php"

headers = {
    "Authorization": "Bearer yourSuperSecretTokenHere",
    "Content-Type": "application/json"
}

payload = {
    "userid": 4,
    "courseid": 2,
    "score": 4,
    "comment": "Passed with a good explanation.!"
}

response = requests.post(API_URL, json=payload, headers=headers)

print("Status Code:", response.status_code)
print("Response Text:", response.text)