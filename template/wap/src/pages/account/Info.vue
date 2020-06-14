<template>
  <Layout ref="load" class="account-info bg-f8">
    <Navbar :isMenu="false" />
    <van-cell-group>
      <van-field
        label="用户名"
        :disabled="info.user_name ? true : false"
        placeholder="只能更改一次"
        v-model="user_name"
      />
      <van-field
        label="昵称"
        placeholder="请输入昵称"
        v-model="info.nick_name"
      />
      <van-field
        label="真实姓名"
        placeholder="请输入真实姓名"
        v-model="info.real_name"
      />
      <FormGroup :items="formList" ref="FormGroup" v-if="isForm" />
      <div>
        <CellDatePopup
          label="生日"
          placeholder="请选择生日"
          :value="info.birthday"
          @confirm="onConfirmDate"
        />
        <van-cell title="性别" class="cell-panel">
          <van-radio-group v-model="info.sex" class="cell-radio-group">
            <van-radio :name="1">男</van-radio>
            <van-radio :name="2">女</van-radio>
            <van-radio :name="0">保密</van-radio>
          </van-radio-group>
        </van-cell>
        <van-field
          label="QQ"
          type="number"
          placeholder="请输入QQ号码"
          v-model="info.qq"
        />
        <CellAreaPopup
          label="所在地"
          placeholder="请选择地区"
          :info="areaInfo"
          @confirm="onAreaConfirm"
        />
      </div>
    </van-cell-group>

    <div class="foot-btn-group">
      <van-button
        size="normal"
        type="danger"
        round
        block
        @click="onSave"
        :loading="isLoading"
        >保存</van-button
      >
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellAreaPopup from "@/components/CellAreaPopup";
import CellDatePopup from "@/components/CellDatePopup";
import { GET_ACCOUNTINFO, SET_ACCOUNTINFO } from "@/api/member";
import { formatDate, isEmpty } from "@/utils/util";
import { validNumber } from "@/utils/validator";
import FormGroup from "@/components/FormGroup";
export default sfc({
  name: "account-info",
  data() {
    return {
      isLoading: false,
      info: {},
      user_name: ""
      // formList: []
    };
  },
  computed: {
    isForm() {
      return !isEmpty(this.formList);
    },
    formList() {
      let list = [];
      if (
        !isEmpty(this.info.custom_data) &&
        this.info.custom_person &&
        this.info.custom_person.status ==
          this.$store.state.config.customForm.member_status
      ) {
        list = this.info.custom_person.form_data;
      } else {
        list = this.info.custom_data;
      }
      return list;
    },
    // 格式化地区信息
    areaInfo() {
      const $this = this;
      let info = {};
      if (!isEmpty($this.info)) {
        info.id = [];
        info.code = $this.info.area_code;
        if ($this.info.province_id) {
          info.id[0] = $this.info.province_id;
          info.text = $this.info.province_name;
        }
        if ($this.info.city_id) {
          info.id[0] = $this.info.province_id;
          info.id[1] = $this.info.city_id;
          info.text = $this.info.province_name + " / " + $this.info.city_name;
        }
        if ($this.info.city_id) {
          info.id[0] = $this.info.province_id;
          info.id[1] = $this.info.city_id;
          info.id[2] = $this.info.district_id;
          info.text =
            $this.info.province_name +
            " / " +
            $this.info.city_name +
            " / " +
            $this.info.district_name;
        }
      }
      // console.log(info);
      return info;
    }
  },
  mounted() {
    const $this = this;
    let info = $this.$store.state.account.info;
    if (isEmpty(info)) {
      $this.$store
        .dispatch("getAccountInfo")
        .then(({ data }) => {
          $this.loadData(data);
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    } else {
      $this.loadData(info);
    }
  },
  methods: {
    loadData(data) {
      const $this = this;
      let info = Object.assign({}, data);
      $this.info = info;
      $this.user_name = info.user_name ? info.user_name : "";
      $this.$refs.load.success();
    },
    onConfirmDate(value) {
      this.info.birthday = value;
    },
    onAreaConfirm(data) {
      this.info.province_name = data.text.split(" / ")[0];
      this.info.city_name = data.text.split(" / ")[1];
      this.info.district_name = data.text.split(" / ")[2];
      this.info.area_code = data.code;
      this.info.province_id = data.id[0];
      this.info.city_id = data.id[1];
      this.info.district_id = data.id[2];
    },
    onSave() {
      const $this = this;
      const params = {};
      const form_data = $this.$refs["FormGroup"]
        ? $this.$refs["FormGroup"].getFormData()
        : "";
      if ($this.user_name) {
        if (!validNumber($this.user_name, "用户名不能为纯数字！")) {
          return false;
        }
        params.user_name = $this.user_name;
      }
      params.nick_name = $this.info.nick_name;
      params.real_name = $this.info.real_name;
      if (!$this.isForm) {
        params.sex = $this.info.sex;
        params.birthday = $this.info.birthday;
        params.qq = $this.info.qq;
        params.province_id = $this.info.province_id;
        params.city_id = $this.info.city_id;
        params.district_id = $this.info.district_id;
        params.area_code = $this.info.area_code;
      } else {
        if (!form_data) return false;
        params.post_data = JSON.stringify({
          form_data,
          status: $this.$store.state.config.customForm.member_status
        });
      }
      // console.log(params);
      // return;
      $this.isLoading = true;
      SET_ACCOUNTINFO(params)
        .then(res => {
          $this.$Toast.success("保存成功");
          $this.isLoading = false;
          setTimeout(() => {
            $this.$store.dispatch("getAccountInfo");
            $this.$router.back();
          }, 500);
        })
        .catch(() => {
          $this.isLoading = false;
        });
    }
  },
  components: {
    CellAreaPopup,
    CellDatePopup,
    FormGroup
  }
});
</script>

<style scoped></style>
