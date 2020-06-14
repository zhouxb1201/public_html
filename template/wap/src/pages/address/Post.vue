<template>
  <Layout ref="load" class="address-post bg-f8">
    <Navbar :title="navbarTitle" :isMenu="false" />
    <van-address-edit
      :area-list="areaList"
      :address-info="address_info"
      show-set-default
      show-postal
      save-button-text="保存"
      :is-saving="is_saving"
      @save="onSave"
      @change-area="onChangeArea"
      class="address-edit"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { AddressEdit } from "vant";
import { GET_ADDRESSDETAIL, SAVE_ADDRESS } from "@/api/address";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "address-post",
  data() {
    return {
      is_saving: false,
      params: {},
      address_info: {}
    };
  },
  computed: {
    navbarTitle() {
      let title = "";
      title = this.$route.query.addressid
        ? "编辑收货地址"
        : this.$route.meta.title;
      if (title) document.title = title;
      return title;
    },
    postType() {
      return this.$route.query.addressid ? "edit" : "add";
    },
    areaList: {
      get() {
        return this.$store.getters.areaList;
      },
      set(e) {
        console.log(e);
      }
    }
  },
  mounted() {
    const $this = this;
    $this.$store
      .dispatch("getArea")
      .then(res => {
        if ($this.$route.query.addressid) {
          $this.getDateil();
        } else {
          $this.$refs.load.success();
        }
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    getDateil() {
      const $this = this;
      GET_ADDRESSDETAIL($this.$route.query.addressid)
        .then(({ data }) => {
          const obj = {};
          obj.id = data.id;
          obj.name = data.consigner;
          obj.tel = data.mobile;
          obj.province = data.province_name;
          obj.city = data.city_name;
          obj.county = data.district_name;
          obj.province_id = data.province;
          obj.city_id = data.city;
          obj.county_id = data.district;
          obj.addressDetail = data.address;
          obj.isDefault = data.is_default === 1 ? true : false;
          obj.areaCode = data.area_code;
          obj.postalCode = data.zip_code;
          $this.address_info = obj;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onChangeArea(e) {
      const $this = this;
      if ($this.postType === "edit") {
        $this.address_info.province_id =
          $this.areaList.province_id_list[e[0].code];
        $this.address_info.city_id = $this.areaList.city_id_list[e[1].code];
        $this.address_info.county_id = $this.areaList.county_id_list[e[2].code];
        // console.log($this.address_info);
      } else {
        $this.params.province = $this.areaList.province_id_list[e[0].code];
        $this.params.city = $this.areaList.city_id_list[e[1].code];
        $this.params.district = $this.areaList.county_id_list[e[2].code];
        // console.log($this.params);
      }
    },
    onSave(data) {
      const $this = this;
      $this.params.id = data.id ? data.id : "";
      $this.params.province = data.province_id
        ? data.province_id
        : $this.params.province;
      $this.params.city = data.city_id ? data.city_id : $this.params.city;
      $this.params.district = data.county_id
        ? data.county_id
        : $this.params.district;
      $this.params.consigner = data.name;
      $this.params.mobile = data.tel;
      $this.params.address = data.addressDetail;
      $this.params.is_default = data.isDefault ? 1 : 0;
      $this.params.area_code = data.areaCode;
      $this.params.zip_code = data.postalCode;
      $this.is_saving = true;
      // console.log($this.params);
      // return;
      SAVE_ADDRESS($this.params).then(res => {
        $this.$Toast.success(res.message);
        setTimeout(() => {
          $this.$router.back(-1);
          $this.is_saving = false;
        }, 1000);
      });
    }
  },
  components: {
    [AddressEdit.name]: AddressEdit
  }
});
</script>

<style scoped>
.van-popup {
  width: 100%;
}
</style>
