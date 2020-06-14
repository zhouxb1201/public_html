<template>
  <Layout ref="load" class="account-post bg-f8">
    <Navbar :title="navbarTitle"/>
    <div v-if="isValid">
      <van-cell-group>
        <van-field label="手机号码" type="tel" maxlength="11" disabled v-model="mobile"/>
        <van-field label="当前密码" type="password" placeholder="请输入当前密码" v-model="password"/>
      </van-cell-group>
      <div class="foot-btn-group">
        <van-button size="normal" type="danger" round block @click="onNext">下一步</van-button>
      </div>
    </div>
    <component v-else :is="componentName" :page-type="pageType"/>
  </Layout>
</template>

<script>
import { validPassword } from "@/utils/validator";
import PostUpdatePassword from "./component/PostUpdatePassword";
import { CHECK_PASSWORD } from "@/api/account";
export default {
  name: "account-post",
  data() {
    return {
      isValid: true,
      mobile: "",
      password: ""
    };
  },
  computed: {
    pageType() {
      return this.$route.params.pagetype;
    },
    navbarTitle() {
      let title = "";
      if (this.pageType == 1) {
        title = "修改登录密码";
      }
      if (title) document.title = title;
      return title;
    },
    componentName() {
      let name = "";
      if (this.pageType == 1) {
        name = "Password";
      }
      return "PostUpdate" + name;
    }
  },
  created() {
    const $this = this;
    $this.$store
      .dispatch("getAccountInfo")
      .then(info => {
        $this.mobile = info.assistant_tel;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    onNext() {
      const $this = this;
      if (!validPassword($this.password)) {
        return false;
      }
      CHECK_PASSWORD($this.password).then(() => {
        // 验证通过成功下一步操作;
        $this.$Toast.success("验证通过");
        $this.isValid = false;
      });
    }
  },
  components: {
    PostUpdatePassword
  }
};
</script>

<style scoped>
</style>
