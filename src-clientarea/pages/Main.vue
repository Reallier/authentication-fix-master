<template>
  <div class="d-flex flex-column mb-3">
    <div class="col-md">
      <div class="alert alert-info" role="alert" v-show="showNoAuth">
        您还没有进行任何认证,在下面继续吧
      </div>
      <div class="alert alert-info" role="alert" v-show="showPhoneAuth">
        您已完成实人认证,可以继续手机认证
      </div>
      <div class="alert alert-info" role="alert" v-show="changePhoneAuth">
        您已完成手机认证,可以继续实人认证
      </div>
      <div class="alert alert-success" role="alert" v-show="showAuthSuccess">
        您已完成全部认证,如需修改手机号,可以在下面进行修改
      </div>
    </div>
    <div class="d-flex flex-row col-md-12 flex-md-nowrap gap-4 mb-3">
      <div class="card w-100">
        <div class="card-body">
          <h5 class="card-title">身份证实名认证</h5>
          <p class="card-text">在这里进行支付宝实人认证</p>
          <p class="card-text" v-show="disableCertAuth">
            当前已验证信息: <br>
            姓名: {{ authInfo.name }}<br>
            身份证号: {{ authInfo.id }}<br>
          </p>
          <button class="btn btn-primary sticky-bottom" :disabled="disableCertAuth" @click="toCertAuth">去认证</button>
        </div>
      </div>
      <div class="card w-100">
        <div class="card-body">
          <h5 class="card-title">手机号验证</h5>
          <p class="card-text">在这里进行手机号验证,或修改手机号</p>
          <p class="card-text" v-show="showPhoneInfo">
            当前已验证信息: <br>
            手机号: {{ authInfo.phone }}<br>
            <br>
          </p>
          <button class="btn btn-primary" @click="toPhoneAuth">去认证/修改</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">


import {AuthStatus} from "~/types/status";
import Swal from "sweetalert2";

definePageMeta({
  path: "/",
  name: "Main",
})


const showNoAuth = ref<boolean>(true)
const showPhoneAuth = ref<boolean>(false)
const showAuthSuccess = ref<boolean>(false)
const disableCertAuth = ref(false)
const changePhoneAuth = ref(false)
const router = useRouter();
const route = useRoute();
const showPhoneInfo = ref(false);
const authInfo = reactive({
  name: "",
  id: "",
  phone: ""
})
const showPopup = ref(false);
showPopup.value = route.query.popup as unknown as boolean
if (showPopup.value) {
  // 弹窗提示没有实名认证
  Swal.fire({
    title: "提示",
    text: "您还没有进行实名认证,请先进行实名认证",
    icon: "info",
    confirmButtonText: "去认证"
  }).then((result) => {
    if (result.isConfirmed) {
      toCertAuth()
    }
  });
}
const toCertAuth = () => {
  router.push({
    name: "CertAuth"
  })
}
const toPhoneAuth = () => {
  router.push({
    name: "PhoneAuth"
  })
}
onMounted(async () => {
  const {data: authStatus, error} = await useFetch<AuthStatus>("/index.php?m=authentication", {
    query: {
      type: "api",
      path: "/status"
    },
    server: false,
  })
  // 解析 JSON 并根据其中的 biz_status 和 phone_status 字段进行判断
  // console.log(authStatus.value!.data.certStatus )
  // 第一步,判断是否显示 什么验证都没有
  showNoAuth.value = !authStatus.value!.data.certStatus && !authStatus.value!.data.phoneStatus
  // 第二步,判断是否显示 已完成实人认证,可以继续手机认证
  showPhoneAuth.value = authStatus.value!.data.certStatus && !authStatus.value!.data.phoneStatus
  // 第三步,判断是否显示 已完成全部认证,如需修改手机号,可以在下面进行修改
  showAuthSuccess.value = authStatus.value!.data.certStatus && authStatus.value!.data.phoneStatus
  // 然后对按钮做设定
  disableCertAuth.value = authStatus.value!.data.certStatus
  changePhoneAuth.value = authStatus.value!.data.phoneStatus && !authStatus.value!.data.certStatus
  // changePhoneAuth.value = true
  // 如果完成手机验证,则显示手机号
  showPhoneInfo.value = authStatus.value!.data.phoneStatus
  // 填充信息
  if (authStatus.value!.data.certStatus){
    authInfo.name = authStatus.value!.data.certInfo!.name
    authInfo.id = authStatus.value!.data.certInfo!.id
  }
  if (authStatus.value!.data.phoneStatus){
    authInfo.phone = authStatus.value!.data.phoneInfo!.phone
  }
})
</script>

<style scoped>

</style>