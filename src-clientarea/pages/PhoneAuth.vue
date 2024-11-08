<template>
  <div class="d-flex flex-row mb-3 gap-3 justify-content-around">
    <div
        class="d-flex flex-column col-md gap-3 justify-content-center"
        @submit.prevent="check"
    >
      <div class="col-12">
        <div class="input-group">
          <input type="text"
                 class="form-control"
                 v-model="phoneForm.phone"
                 placeholder="手机号,不需要 +86"
                 required>
          <button class="btn btn-primary" @click="sendCode">发送</button>
        </div>
      </div>

      <div class="col-12">
        <div class="input-group">
          <input type="text"
                 v-model="phoneForm.code"
                 class=""
                 placeholder="验证码"
                 required>
        </div>
      </div>
      <!--        阅读和同意条款-->
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input"  v-model="phoneForm.agreed" type="checkbox" required >
          <label class="form-check-label">
            阅读并同意下列条款
          </label>
        </div>
      </div>
      <div class="col-12">
        <div class="alert alert-danger">
          <p>
            <strong
            >为更好的享受 {{ authStatus.data.companyName }}
             提供的服务，本人知晓并同意腾讯云有权基于提供征信服务的需要向合法保存有本人信息的机构采集本人信息（包括由
             {{ authStatus.data.companyName }}
             有权将本人在使用其服务过程中提交或产生的信息提供给腾讯云），用于验证本人信息的真实性及提供征信服务使用。本人授权腾讯云，可根据
             {{ authStatus.data.companyName }}
             的查询指令，向其提供相关信息的真实性判断结果及本人的信用信息，用以交易决策使用。</strong
            >
          </p>
        </div>
      </div>

      <div class="col-12 d-flex flex-md-row flex-column gap-3">
        <button
            type="submit"
            class="btn btn-primary"
            @click="check"
            :disabled="checkBtnDisabled"
        >
          {{ checkBtnText }}
        </button>
        <button @click="goBack" type="submit" class="btn btn-secondary">
          返回
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Swal from "sweetalert2";
import {PhoneCheckResult} from "~/types/checkResults";
import {VerificationCodeResult} from "~/types/codeResult";
import {AuthStatus, PhoneStatus} from "~/types/status";

definePageMeta({
  path: "/phoneauth",
  name: "PhoneAuth",
});

const phoneForm = reactive({
  phone: "",
  code: "",
  agreed: false,
});
const checkBtnText = ref("请先发送验证码")
const checkBtnDisabled = ref(true)
const router = useRouter();
// 定义提交按钮

const {data: authStatus} = await useFetch<AuthStatus>("/index.php?m=authentication", {
  server: false,
  query: {
    type: "api",
    path: "/status",
  },
});
const sendCode = async () => {
  // 先给我检查手机号
  if (!phoneForm.phone) {
    await Swal.fire({
      title: "出错了!",
      text: "手机号不能为空",
      icon: "error",
      confirmButtonText: "再检查一下",
    });
    return;
  }
  // 还有长度
  if (phoneForm.phone.length !== 11) {
    await Swal.fire({
      title: "出错了!",
      text: "手机号长度不正确",
      icon: "error",
      confirmButtonText: "再检查一下",
    });
    return;
  }
  // 还有是否同意
  if (!phoneForm.agreed) {
    await Swal.fire({
      title: "出错了!",
      text: "请先同意条款",
      icon: "error",
      confirmButtonText: "知道了",
    });
    return;
  }
  await $fetch(
      "/index.php?m=authentication",
      {
        method: "POST",
        query: {
          type: "api",
          path: "/phoneauth/sendcode",
        },
        body: {
          data: {
            phone: phoneForm.phone,
          },
        },
      }
  ).then((resp: VerificationCodeResult) => {
    if (!resp.success) {
      if (resp.data.remain) {
        Swal.fire({
          title: "发送太频繁啦",
          text: `等 ${resp.data.remain} 秒再发送哦!`,
          icon: "error",
          confirmButtonText: "知道了",
        });
        // 说明发过了,此时应该允许按钮
        checkBtnDisabled.value = false
        checkBtnText.value = "提交"
        return;
      }
      // 出现了错误
      Swal.fire({
        title: "出错了!",
        text: resp.data.message,
        icon: "error",
        confirmButtonText: "再检查一下",
      });
      return;
    }
    // 成功了,提示已发送
    Swal.fire({
      title: "成功!",
      text: "已发送验证码,注意查收",
      icon: "success",
      confirmButtonText: "好",
    }).then(() => {
    });
    // 激活按钮并修改文本
    checkBtnDisabled.value = false
    checkBtnText.value = "提交"

  });
}
const check = async () => {
  // 需要自己同意
  if (!phoneForm.agreed) {
    await Swal.fire({
      title: "出错了!",
      text: "请先同意条款",
      icon: "error",
      confirmButtonText: "知道了",
    });
    return;
  }
  await $fetch(
      "/index.php?m=authentication",
      {
        method: "POST",
        query: {
          type: "api",
          path: "/phoneauth/check",
        },
        body: {
          data: {
            phone: phoneForm.phone,
            code: phoneForm.code,
          },
        },
      }
  ).then((resp: PhoneCheckResult) => {
    // console.log(resp.success)
    if (!resp.success) {
      // 出现了错误
      Swal.fire({
        title: "出错了!",
        text: resp.data.message,
        icon: "error",
        confirmButtonText: "再检查一下",
      });
      return;
    }
    // 验证成功,
    Swal.fire({
      title: "认证成功!",
      text: "认证成功,请返回主页",
      icon: "success",
      confirmButtonText: "返回主页",
    }).then(() => {
      authStatus.value!.data.certStatus = true;
      router.push({
        name: "Main",
      });
    });
  });

};
const goBack = async () => {
  router.push({
    name: "Main",
  });
};
onMounted(async () => {
  const {data: phoneStatus, error} = await useFetch<PhoneStatus>("/index.php?m=authentication", {
    query: {
      type: "api",
      path: "/phoneauth/status"
    },
    server: false,
  })
  // 如果验证码已经发出,则激活按钮
  // 同时文本修改为开始认证
  if (phoneStatus.value?.data.hasCodeSend) {
    checkBtnDisabled.value = false
    checkBtnText.value = "提交"
  }

});
onUnmounted(() => {
})
</script>

<style scoped></style>
