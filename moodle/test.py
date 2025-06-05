import requests

API_URL = "https://test.getecho.io/moodle/filter/echo_url/api.php"

headers = {
    "Authorization": "Bearer yourSuperSecretTokenHere",
    "Content-Type": "application/json"
}

payload = {
    "userid": 4,
    "courseid": 4,
    "gradeitem": "Echo: Pearl Harbor (6)",
    "score": 65,
    "review": "Passed with a good explana!"
}

response = requests.post(API_URL, json=payload, headers=headers)

print("Status Code:", response.status_code)
print("Response Text:", response.text)