<template>
  <div class="d-flex flex-row mb-3 gap-3 justify-content-around">
    <!--    这一列,存放实名认证输入内容-->
    <div
        class="d-flex flex-column col-md gap-3 justify-content-between"
        @submit.prevent="submit"
    >
      <form class="row g-3">
        <div class="col-12">
          <label class="form-label">姓名</label>
          <input
              type="text"
              class="form-control"
              v-model="certForm.certName"
              required
          />
          <div class="form-text">请输入姓名.</div>
        </div>
        <div class="col-12">
          <label class="form-label">身份证号</label>
          <input
              type="text"
              class="form-control"
              v-model="certForm.certNo"
              required
          />
          <div class="form-text">请输入身份证号.</div>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input
                type="checkbox"
                class="form-check-input"
                v-model="certForm.agreed"
                required
            />
            <label class="form-check-label">阅读并同意下列条款</label>
          </div>
        </div>
        <div class="col-12" style="">
          <div class="alert alert-danger">
            <p>
              <strong
              >为更好的享受 {{ authStatus.data.companyName }}
               提供的服务，本人知晓并同意芝麻信用有权基于提供征信服务的需要向合法保存有本人信息的机构采集本人信息（包括由
               {{ authStatus.data.companyName }}
               有权将本人在使用其服务过程中提交或产生的信息提供给芝麻信用），用于验证本人信息的真实性及提供征信服务使用。本人授权芝麻信用，可根据
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
              @submit="submit"
              v-if="!authStatus.data.certStatus"
          >
            开始认证
          </button>
          <button @click="goBack" type="submit" class="btn btn-secondary">
            返回
          </button>
        </div>
      </form>
    </div>
    <!--    放置二维码-->
    <div class="d-flex flex-column col-md">
      <div class="mb-3" v-show="showQrCode">
        <img :src="qrCodeData" class="img-fluid rounded mx-auto d-block" alt="二维码"/>
      </div>
      <div class="mb-3" v-show="showQrCode">
        <p class="text-center">请使用支付宝扫描二维码进行实名认证</p>
      </div>
      <div class="mb-3" v-show="!showQrCode">
        <p class="text-center">请在左侧填写信息,然后按下 "开始认证"</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Swal from "sweetalert2";
import QRCode from "qrcode";
import {useIntervalFn} from "@vueuse/core"
import {CertCheckResult} from "~/types/checkResults";
import {QrCodeResult} from "~/types/codeResult";
import {AuthStatus} from "~/types/status";

definePageMeta({
  path: "/certauth",
  name: "CertAuth",
});

const certForm = reactive({
  certNo: "",
  certName: "",
  agreed: false,
});
const qrCodeData = ref("");
const showQrCode = ref(false);
const router = useRouter();
// 定义提交按钮

const {data: authStatus} = await useFetch<AuthStatus>("/index.php?m=authentication", {
  server: false,
  query: {
    type: "api",
    path: "/status",
  },
});
const submit = async () => {
  await $fetch(
      "/index.php?m=authentication",
      {
        method: "POST",
        query: {
          type: "api",
          path: "/certauth/submit",
        },
        body: {
          data: {
            certName: certForm.certName,
            certNo: certForm.certNo,
          },
        },
      }
  ).then((resp:QrCodeResult) => {
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
    QRCode.toDataURL(resp.data.code as string).then(url => {
      // console.log(url);
      qrCodeData.value = url;
      showQrCode.value = true;
    });
  });
  // 开启轮询


  const {pause, resume, isActive} = useIntervalFn(async () => {
    await $fetch(
        "/index.php?m=authentication", {
          method: "POST",
          query: {
            type: "api",
            path: "/certauth/check",
          },
          body: {
            data: {
              certName: certForm.certName,
              certNo: certForm.certNo,
            },
          },
        }
    ).then((resp: CertCheckResult) => {
      if (resp.data.certStatus) {
        console.log("认证成功");
        pause();
        // 说明已经认证成功
        showQrCode.value = false;
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
      }
    })
    console.log("认证不成功,刷新数据");
    // 认证不成功,刷新数据
  }, 5000)

};
const goBack = async () => {
  router.push({
    name: "Main",
  });
};
onMounted(async () => {
});
onUnmounted(() => {
})
</script>

<style scoped></style>
